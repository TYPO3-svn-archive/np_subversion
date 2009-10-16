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

/**
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_filestatus {

	/**
	 * @var string absolute path of the file/folder
	 */
	protected $path;

	/**
	 * @var string at position 1 of the status line
	 */
	protected $textStatus;

	/**
	 * @var string at position 2 of the status line
	 */
	protected $propertyStatus;

	/**
	 * @var string at position 4 of the status line
	 */
	protected $historyStatus;

	/**
	 * @var string at position 5 of the status line
	 */
	protected $switchStatus;

	/**
	 * @var string at position 6 of the status line
	 */
	protected $lockStatus;

	/**
	 * @var string at position 7 of the status line
	 */
	protected $obsoleteStatus;

	/**
	 * @var array text status labels
	 */
	protected $textStatusLabels = array(
		'A' => 'added',
		'D' => 'deleted',
		'M' => 'modified',
		'C' => 'conflicted',
		'X' => 'external',
		'I' => 'ignored',
		'?' => 'non-versioned',
		'!' => 'missing',
		'~' => 'obstructed',
	);

	/**
	 * @var array property status labels
	 */
	protected $propertyStatusLabels = array(
		'M' => 'modified',
		'C' => 'conflicted',
	);

	/**
	 * @var array history status labels
	 */
	protected $historyStatusLabels = array(
		'M' => 'modified',
		'C' => 'conflicted',
	);

	/**
	 * @var array switch status labels
	 */
	protected $switchStatusLabels = array(
	);

	/**
	 * @var array lock status labels
	 */
	protected $lockStatusLabels = array(
		'L' => 'locked (L)',
		'K' => 'locked (K)',
		'O' => 'locked (O)',
		'T' => 'locked (T)',
		'B' => 'locked (B)',
	);

	/**
	 * @var array obsolete status labels
	 */
	protected $obsoleteStatusLabels = array(
	);

	/**
	 * @return string absolute path of the file/folder
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getTextStatus() {
		return $this->textStatus;
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getLockStatus() {
		return $this->lockStatus;
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getTextStatusLabel() {
		return $this->getStatusLabel($this->textStatusLabels, $this->textStatus);
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getPropertyStatusLabel() {
		return $this->getStatusLabel($this->propertyStatusLabels, $this->propertyStatus);
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getHistoryStatusLabel() {
		return $this->getStatusLabel($this->historyStatusLabels, $this->historyStatus);
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getSwitchStatusLabel() {
		return $this->getStatusLabel($this->switchStatusLabels, $this->switchStatus);
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getLockStatusLabel() {
		return $this->getStatusLabel($this->lockStatusLabels, $this->lockStatus);
	}

	/**
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getObsoleteStatusLabel() {
		return $this->getStatusLabel($this->obsoleteStatusLabels, $this->obsoleteStatus);
	}

	/**
	 * Returns "human readable" label for a given status code 
	 * 
	 * @param array $labelArray
	 * @param string status
	 * @return string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getStatusLabel($labelArray, $status) {
		if (strlen($status) === 0 || $status === ' ') {
			return '';
		}
		if (isset($labelArray[$status])) {
			return $labelArray[$status];
		}
		return '';
	}

	/**
	 * Constructor.
	 *
	 * @param string $path absolute path of the file/folder
	 * @param string $textStatus
	 * @param string $propertyStatus
	 * @param string $historyStatus
	 * @param string $switchStatus
	 * @param string $lockStatus
	 * @param string $obsoleteStatus
	 */
	public function __construct($path, $textStatus, $propertyStatus, $historyStatus, $switchStatus, $lockStatus, $obsoleteStatus) {
		$this->path = $path;
		$this->textStatus = $textStatus;
		$this->propertyStatus = $propertyStatus;
		$this->historyStatus = $historyStatus;
		$this->switchStatus = $switchStatus;
		$this->lockStatus = $lockStatus;
		$this->obsoleteStatus = $obsoleteStatus;
	}

	/**
	 * @param array $svnStatusArray lines returned by svn status call
	 * @return array list of filestatus instances
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function createFromSVNStatusArray(array $svnStatusArray) {
		$filestatusArray = array();
		foreach($svnStatusArray as $svnStatusLine) {
			$filestatus = self::createFromSVNStatusLine($svnStatusLine);
			$filePath = $filestatus->getPath();
			if (empty($filePath)) {
				continue;
			}
			$filestatusArray[$filePath] = $filestatus;
		}

		return $filestatusArray;
	}

	/**
	 * @param string svn status line
	 * @return object instance of filestatus
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function createFromSVNStatusLine($svnStatusLine) {
		$path = trim(substr($svnStatusLine, 7));
		$path = t3lib_div::fixWindowsFilePath($path);

		$textStatus = substr($svnStatusLine, 0, 1);
		$propertyStatus = substr($svnStatusLine, 1, 1);
		$historyStatus = substr($svnStatusLine, 3, 1);
		$switchStatus = substr($svnStatusLine, 4, 1);
		$lockStatus = substr($svnStatusLine, 5, 1);
		if ($lockStatus === ' ') {
			$lockStatus = substr($svnStatusLine, 2, 1);
		}
		$obsoleteStatus = substr($svnStatusLine, 6, 1);

		$filestatusClassName = t3lib_div::makeInstanceClassName('tx_npsubversion_filestatus');
		$filestatus = new $filestatusClassName($path, $textStatus, $propertyStatus, $historyStatus, $switchStatus, $lockStatus, $obsoleteStatus);
		return $filestatus;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_filestatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_filestatus.php']);
}

?>
