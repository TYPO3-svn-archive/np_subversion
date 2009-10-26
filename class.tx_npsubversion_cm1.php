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

require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_svn.php');

/**
 * Clickmenu setup for the 'np_subversion' extension.
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_cm1 {

	/**
	 * @var tx_npsubversion_svn
	 */
	protected $svn;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function __construct() {
		$this->svn = t3lib_div::makeInstance('tx_npsubversion_svn');
		$this->svn->setSvnPath($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svn_path']);
		$this->svn->setSvnConfigDir($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svn_config_dir']);
		$this->svn->setUmask($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['umask']);
		$this->svn->setUsePassthru($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['use_passthru']);
		$this->svn->setCommandSuffix($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['command_suffix']);
	}

	/**
	 * Adds clickmenu entries to files/folders that are under version control
	 * 
	 * @return array modified menuItems
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function main(&$backRef, $menuItems, $path, $uid) {
			// page / tt_content -> return
		if ($path === 'pages' || $uid > 0) {
			return $menuItems;
		}
			// second level menu -> return
		if (!empty($backRef->cmLevel)) {
			return $menuItems;
		}
			// no admin -> return
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			return $menuItems;
		}

		$LL = $GLOBALS['LANG']->includeLLFile('EXT:np_subversion/locallang.xml', FALSE);

		$localItems = Array();
		$localItems[] = 'spacer';
		if ($this->svn->isWorkingCopy($path)) {
			$fileStatus = $this->svn->getFileStatus($path);

				// update
			$url = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/index.php?path=' . urlencode($path) . '&cmd=update';
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('cm1_update',$LL),
				$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/update.gif" width="16" height="16" border="0" align="top" />'),
				$backRef->urlRefForCM($url),
				0
			);
				// commit
			$url = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/index.php?path=' . urlencode($path) . '&cmd=commit';
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('cm1_commit',$LL),
				$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/commit.gif" width="16" height="16" border="0" align="top" />'),
				$backRef->urlRefForCM($url),
				0
			);
				// log
			$url = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/index.php?path=' . urlencode($path) . '&cmd=log';
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('cm1_log',$LL),
				$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/log.gif" width="16" height="16" border="0" align="top" />'),
				$backRef->urlRefForCM($url),
				0
			);
				// diff
			if (($fileStatus === 'modified' || $fileStatus === 'workingcopy') && is_file($path)) {
				$url = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/index.php?path=' . urlencode($path) . '&cmd=diff';
				$localItems[] = $backRef->linkItem(
					$GLOBALS['LANG']->getLLL('cm1_diff',$LL),
					$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/diff.gif" width="16" height="16" border="0" align="top" />'),
					$backRef->urlRefForCM($url),
					0
				);
			}
				// revert
			if ($fileStatus === 'modified' || $fileStatus === 'deleted') {
				$url = t3lib_extMgm::extRelPath('np_subversion').'cm1/index.php?path=' . urlencode($path) . '&cmd=revert';
				$localItems[] = $backRef->linkItem(
					$GLOBALS['LANG']->getLLL('cm1_revert',$LL),
					$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/revert.gif" width="16" height="16" border="0" align="top" />'),
					$backRef->urlRefForCM($url),
					0
				);
			}
				// delete
			$url = t3lib_extMgm::extRelPath('np_subversion').'cm1/index.php?path=' . urlencode($path) . '&cmd=delete';
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('cm1_delete',$LL),
				$backRef->excludeIcon('<img src="' . t3lib_extMgm::extRelPath("np_subversion") . 'res/icons/delete.gif" width="16" height="16" border="0" align="top" />'),
				$backRef->urlRefForCM($url),
				0
			);
		}
		$menuItems = array_merge($menuItems, $localItems);

		return $menuItems;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_cm1.php']);
}
?>
