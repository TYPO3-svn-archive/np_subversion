<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Bastian Waidelich <waidelich@network-publishing.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('np_subversion').'class.tx_npsubversion_svn.php');
require_once(t3lib_extMgm::extPath('np_subversion').'class.tx_npsubversion_model.php');

/**
 * XCLASS of the TYPO3 filelistFolderTree class
 * might be replaced by hooks soon
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class ux_filelistFolderTree extends filelistFolderTree {

	/**
	 * @var tx_npsubversion_svn
	 */
	protected $svn;

	/**
	 * @var tx_npsubversion_model
	 */
	protected $model;

	/**
	 * @var unknown_type
	 */
	protected $workingCopies;

	/**
	 * @var unknown_type
	 */
	protected $exportTargets;

	/**
	 * @var unknown_type
	 */
	protected $folderIcon;

	/**
	 * Constructor. initializes class fields
	 *
	 * @return ux_filelistFolderTree
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function __construct() {
		$this->svn = t3lib_div::makeInstance('tx_npsubversion_svn');
		$this->svn->setSvnPath($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svn_path']);
		$this->svn->setSvnConfigDir($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svn_config_dir']);
		$this->svn->setUmask($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['umask']);
		$this->svn->setUsePassthru($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['use_passthru']);

		$this->model = t3lib_div::makeInstance('tx_npsubversion_model');
		$this->workingCopies = $this->model->getWorkingCopies();
		$this->exportTargets = $this->model->getExportTargets();

		$this->folderIcon = '<img'.t3lib_iconWorks::skinImg($this->backPath, 'gfx/i/_icon_webfolders.gif', 'width="18" height="16"').' alt="" />';

		parent::__construct();
	}

	/**
	 * overrides filelistFolderTree::wrapIcon() to display Subversion overlay icons (only if BE user is admin)
	 *
	 * @param string $icon Icon IMG code
	 * @param array $row Data row for element.
	 * @return string Page icon
	 * @see filelistFolderTree::wrapIcon()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function wrapIcon($icon, &$row) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$icon = $this->getSVNIcon($icon, $row['path']);
		}

		return parent::wrapIcon($icon, $row);
	}

	/**
	 * finds out Subversion status (modified, new, ...) of $path and adds overlay icon to the current icon by the use of CSS
	 *
	 * @param string $icon current icon code
	 * @param string $path absolute path
	 * @return string modified icon code
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getSVNIcon($icon, $path) {
		if (!is_dir($path)) {
			return $this->svn->overlayIcon($icon, t3lib_extMgm::extRelPath('np_subversion').'res/icons/overlay_deleted.gif', $path);
		}
		if (!$this->svn->isWorkingCopy($path)) {
			return $icon;
		}

		$overlayIcon = $this->svn->getFileStatus($path);
		if (empty($overlayIcon)) {
			return $icon;
		}

		$matches = null;
		preg_match('/src="(.*?)"/', $icon, $matches);
		$iconSrc = $matches[1];

		return'<img src="'.t3lib_extMgm::extRelPath('np_subversion').'res/icons/overlay_'.$overlayIcon.'.gif" width="16" height="16" alt="" title="'.htmlspecialchars($path).'" style="background: transparent url(\''.$iconSrc.'\')" />';
	}

	/**
	 * overrides filelistFolderTree::getBrowsableTree() to add the list of configured target definitions below the existing folder tree
	 *
	 * @return string existing content returned from filelistFolderTree::getBrowsableTree() plus np_subversion panel
	 * @see filelistFolderTree::getBrowsableTree()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getBrowsableTree() {
		$out = parent::getBrowsableTree();
			// if user is no admin or request is an AJAX-call, SVN box we don't display the SVN box
		if (!t3lib_div::_GP('ajax') && !t3lib_div::_GP('ajaxID') && $GLOBALS['BE_USER']->isAdmin()) {
			$out .= $this->getSVNTree();
		}
		return $out;
	}

	/**
	 * overrides filelistFolderTree::getFolderTree() to hide .svn directories.
	 * Unfortunately this method is huge, and though we only have to add a few lines (between <np_subversion> and </np_subversion>) we have to replace the whole method.
	 * A hook would be nice.
	 * 
	 * @param string Abs file path
	 * @param integer Max depth (recursivity limit)
	 * @return integer The count of items on the level
	 * @see filelistFolderTree::getFolderTree()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getFolderTree($files_path, $depth = 999) {

			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);
		if (!is_array($dirs)) return 0;

		// <np_subversion>
		if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['show_svn_dirs']) {
			foreach($dirs as $key => $val)	{
				if ($val === '.svn') {
					array_splice($dirs, $key, 1);
				}
			}
		}
		// </np_subversion>

		sort($dirs);
		$c = count($dirs);

		$depth = intval($depth);
		$HTML = '';
		$a = 0;

		foreach($dirs as $key => $val)	{
			$a++;
			$this->tree[] = array();	// Reserve space.
			end($this->tree);
			$treeKey = key($this->tree);	// Get the key for this space

			$val = ereg_replace('^\./','',$val);
			$title = $val;
			$path = $files_path.$val.'/';

			$specUID = t3lib_div::md5int($path);
			$this->specUIDmap[$specUID] = $path;

			$row = array();
			$row['path']  = $path;
			$row['uid']   = $specUID;
			$row['title'] = $title;

			// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($specUID))	{
				$nextCount = $this->getFolderTree(
					$path,
					$depth-1,
					$this->makeHTML ? '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.($a == $c ? 'blank' : 'line').'.gif','width="18" height="16"').' alt="" />' : ''
				);
				$exp = 1;	// Set "did expand" flag
			} else {
				$nextCount = $this->getCount($path);
				$exp = 0;	// Clear "did expand" flag
			}

				// Set HTML-icons, if any:
			if ($this->makeHTML)	{
				$HTML = $this->PMicon($row,$a,$c,$nextCount,$exp);

				$icon = 'gfx/i/_icon_'.t3lib_BEfunc::getPathType_web_nonweb($path).'folders.gif';
				if ($val == '_temp_')	{
					$icon = 'gfx/i/sysf.gif';
					$row['title']='TEMP';
					$row['_title']='<b>TEMP</b>';
				}
				if ($val == '_recycler_')	{
					$icon = 'gfx/i/recycler.gif';
					$row['title']='RECYCLER';
					$row['_title']='<b>RECYCLER</b>';
				}
				$HTML .= $this->wrapIcon('<img'.t3lib_iconWorks::skinImg($this->backPath, $icon, 'width="18" height="16"').' alt="" />',$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = Array(
				'row'    => $row,
				'HTML'   => $HTML,
				'hasSub' => $nextCount && $this->expandNext($specUID),
				'isFirst'=> ($a == 1),
				'isLast' => FALSE,
				'invertedDepth'=> $depth,
				'bank'   => $this->bank
			);
		}

		if($a) { $this->tree[$treeKey]['isLast'] = TRUE; }
		return $c;
	}

	/**
	 * Creates a list of configured target definitions (working copies/export targets) to be displayed underneath the existing TYPO3 folder tree
	 *
	 * @return string np_subversion panel
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getSVNTree() {
		$content = '';
		if (count($this->workingCopies) === 0 && count($this->exportTargets) === 0) {
			return $content;
		}

		$content .= '
			<table style="margin: 15px 0; border: 1px dotted gray; background: #fff; width: 100%">
		';
		if (count($this->workingCopies) > 0) {
			$content .= '
				<tr>
					<th colspan="2"><h3 class="uppercase">SVN Workingcopies</h3></th>
				</tr>';
			$lastTargetType = -1;
			foreach($this->workingCopies as $workingCopy) {
				if ($workingCopy->getTargetType() !== $lastTargetType) {
					$content .= '
						<tr>
							<td colspan="2" style="padding-top: 5px; border-bottom: 1px solid #f0f0f0">' . ($workingCopy->isFolder() ? 'directories' : 'extensions') . '</td>
						</tr>';
					$lastTargetType = $workingCopy->getTargetType();
				}
				$content .= $this->getSVNTreeItem($workingCopy);
			}
		}
		if (count($this->exportTargets) > 0) {
			$content .= '
				<tr>
					<th colspan="2"><h3 class="uppercase">SVN Export targets</h3></th>
				</tr>';
			$lastTargetType = -1;
			foreach($this->exportTargets as $exportTarget) {
				if ($exportTarget->getTargetType() != $lastTargetType) {
					$content .= '
						<tr>
							<td colspan="2" style="padding-top: 5px; border-bottom: 1px solid #f0f0f0">' . ($exportTarget->isFolder() ? 'directories' : 'extensions') . '</td>
						</tr>';
					$lastTargetType = $exportTarget->getTargetType();
				}
				$content .= $this->getSVNTreeItem($exportTarget);
			}
		}
		$content .= '</table>';

		return $content;
	}

	/**
	 * one line in the "SVNTree"
	 * Called from getSVNTree()
	 *
	 * @param tx_npsubversion_workingcopy $workingCopy reference to working copy record
	 * @return string svn tree row
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getSVNTreeItem($workingCopy) {
		$content = '';
		$workingCopyTitle = $workingCopy->getTitle();
		$onClickAction = '';
		if (is_dir($workingCopy->getAbsolutePath())) {
			if ($workingCopy->isFolder()) {
				$onClickAction = 'return jumpTo(\'' . urlencode($workingCopy->getAbsolutePath()) . '\',this);this.blur();return false;';
			} else {
				$onClickAction = 'top.goToModule(\'tools_em\', 0, \'CMD[showExt]=' . $workingCopy->getExtensionKey() . '&SET[singleDetails]=info\');this.blur();return false;';
			}
		} else {
			$workingCopyTitle = '<em>' . $workingCopyTitle . '</em>';
		}
		$content .= '<tr><td style="padding-left: 5px;">';
		$content .= '<a href="#" title="' . htmlspecialchars($workingCopy->getAbsolutePath()) . '" onclick="' . $onClickAction . '" />';
		$content .= $this->getSVNIcon($this->folderIcon, $workingCopy->getAbsolutePath());
		$content .= '&nbsp;' . $workingCopyTitle . '</a>';
		$content .= '</td><td style="text-align: right">';
		$content .= $this->svnToolIcons($workingCopy);
		$content .= '</td></tr>';

		return $content;
	}

	/**
	 * Gets a list of subversion tool icons depending on whether item is a working copy or an export target
	 *
	 * @param tx_npsubversion_workingcopy $workingCopy reference to working copy record
	 * @return string svn tool icon applicable for specified working copy (checkout/update/commit/export)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function svnToolIcons($workingCopy) {
		$content = '';
		if ($workingCopy->getType() === 0) {
			if (!$this->svn->isWorkingCopy($workingCopy->getAbsolutePath())) {
				$content .= $this->svnToolIcon($workingCopy, 'checkout');
			} else {
				$content .= $this->svnToolIcon($workingCopy, 'update');
				$content .= $this->svnToolIcon($workingCopy, 'commit');
			}
		} else if ($workingCopy->getType() === 1) {
			$content .= $this->svnToolIcon($workingCopy, 'export');
		}

		return $content;
	}

	/**
	 * Creates a clickable SVN icon
	 *
	 * @param tx_npsubversion_workingcopy $workingCopy reference to working copy record
	 * @param string $svnCommand Subversion command
	 * @return string svn tool icon applicable for specified working copy (checkout/update/commit/export)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function svnToolIcon($workingCopy, $svnCommand) {
		$url = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/index.php?wc=' . $workingCopy->getUid() . '&cmd=' . $svnCommand;
		$loc = 'top.content' . ($this->listFrame && !$this->alwaysContentFrame ? '.list_frame' : '');
		$onClick = 'var docRef=(top.content.list_frame)?top.content.list_frame:' . $loc . '; docRef.location.href=top.TS.PATH_typo3+\'' . $url . '\'';

		return ' <a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($svnCommand) . '"><img src="' . t3lib_extMgm::extRelPath('np_subversion') . 'res/icons/' . $svnCommand . '.gif" width="16" height="16" /></a>';
	}
}
?>
