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

/**
 * XCLASS of the TYPO3 fileList
 * might be replaced by hooks soon
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class ux_fileList extends fileList {

	/**
	 * @var tx_npsubversion_svn
	 */
	protected $svn;

	/**
	 * Constructor.
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
	 * overrides fileList::generateList() to fetch Subversion status for the current path
	 *
	 * @see fileList::generateList
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function generateList()	{
			// generate svn status cache
		$this->svn->getFileStatus($this->path, TRUE);
		parent::generateList();
	}

	/**
	 * overrides fileList::formatDirList() to inject Subversion information in the filelist directory collection
	 *
	 * @param array $items list of directories
	 * @return string HTML table rows
	 * @see fileList::formatDirList()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function formatDirList($items) {
		$items = $this->injectSVNStatus($items);

		return parent::formatDirList($items);
	}

	/**
	 * overrides fileList::formatFileList() to inject Subversion information in the filelist file collection
	 *
	 * @param array $items list of files
	 * @return string HTML table rows
	 * @see fileList::formatDirList()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function formatFileList($items) {
		$items = $this->injectSVNStatus($items);

		return parent::formatFileList($items);
	}

	/**
	 * adds Subversion filestatus information to the list of items and removes ".svn" entries
	 * Called from formatDirList() and formatFileList()
	 *
	 * @param array $items list of directories/files
	 * @return array processed items collection
	 * @see formatDirList()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function injectSVNStatus($items) {
			// add status field to columns so it will be included in $data for addElement
		if (!array_search('np_subversion_status', $this->fieldArray)) {
			$this->fieldArray[] = 'np_subversion_status';
		}
		for($i = 0; $i < count($items['files']); $i ++) {
				// skip .svn-folders, if not disabled
			if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['show_svn_dirs'] && $items['files'][$i]['file'] === '.svn') {
				unset($items['files'][$i]);
				unset($items['sorting'][$i]);
				$i--;
				continue;
			}
			$path = $items['files'][$i]['path'].$items['files'][$i]['file'];
			$items['files'][$i]['np_subversion_status'] = $this->svn->getFileStatus($path);
		}
		return $items;
	}

	/**
	 * overrides fileList::addElement() to add overlay icons to directory/file icons
	 * @param integer $h is an integer >=0 and denotes how tall a element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
	 * @param string $icon is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
	 * @param array $data is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
	 * @param string $tdParams is insert in the <td>-tags. Must carry a ' ' as first character
	 * @param integer OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (integer)
	 * @param string $altLine is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 * @return string HTML content for the table row
	 * @see formatDirList()
	 * @see t3lib_recordList::addElement()
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function addElement($h, $icon, $data, $tdParams='', $lMargin='', $altLine='') {
		if ($data['np_subversion_status'] !== FALSE && !empty($icon) && $GLOBALS['BE_USER']->isAdmin()) {
			$overlayIcon = 'res/icons/overlay_' . $data['np_subversion_status'] . '.gif';
			if (file_exists(t3lib_extMgm::extPath('np_subversion') . $overlayIcon)) {
				$icon = $this->svn->overlayIcon($icon, t3lib_extMgm::extRelPath('np_subversion') . $overlayIcon);
			}
		}
		return parent::addElement($h, $icon, $data, $tdParams, $lMargin, $altLine);
	}
}
?>