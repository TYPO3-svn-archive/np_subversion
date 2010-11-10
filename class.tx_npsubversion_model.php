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

require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_workingcopy.php');

/**
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_model {

	/**
	 * cache for working copies
	 *
	 * @var array of tx_npsubversion_workingcopy instances
	 */
	protected $workingCopies = NULL;

	/**
	 * Returns a single tx_npsubversion_workingcopy instance
	 *
	 * @param integer $uid uid of the tx_npsubversion_workingcopy
	 * @return mixed fetched tx_npsubversion_workingcopy or FALSE if no matching record could be found
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getByUid($uid) {
		$workingCopies = $this->loadWorkingCopies('AND tx_npsubversion_workingcopy.uid = ' . intval($uid));
		if (count($workingCopies) === 0) {
			return FALSE;
		}
		return $workingCopies[0];
	}

	/**
	 * Returns all tx_npsubversion_workingcopy instances
	 *
	 * @return array of tx_npsubversion_workingcopy instances
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getWorkingCopies() {
		if ($this->workingCopies === NULL) {
			$this->workingCopies = $this->loadWorkingCopies('AND wc_type = 0');
		}
		return $this->workingCopies;
	}

	/**
	 * Returns a single tx_npsubversion_workingcopy instance by a given path
	 *
	 * @param string $path of a file to fetch matching tx_npsubversion_workingcopy instance for
	 * @return mixed fetched tx_npsubversion_workingcopy or FALSE if no matching record could be found
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getByPath($path) {
		$workingCopies = $this->loadWorkingCopies('AND wc_type = 0 AND wc_target_type = 0 AND LEFT(' . $this->fullQuoteString($path) . ', LENGTH(wc_path)) = wc_path', 1);
		if (count($workingCopies) === 0) {
			return FALSE;
		}
		return $workingCopies[0];
	}

	/**
	 * Returns all tx_npsubversion_workingcopy instances with wc_type of 1 (export target)
	 *
	 * @return array of tx_npsubversion_workingcopy
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getExportTargets() {
		$exportTargets = $this->loadWorkingCopies('AND wc_type = 1');
		return $exportTargets;
	}

	/**
	 * Loads working copy records from the database and creates tx_npsubversion_workingcopy instances from it.
	 *
	 * @param string $addWhere to be appended to WHERE part of the query
	 * @param integer $limit if set, only the given amount of rows will be fetched
	 * @return array of tx_npsubversion_workingcopy instances
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function loadWorkingCopies($addWhere, $limit = '') {
		$where = 'tx_npsubversion_workingcopy.deleted = 0 AND tx_npsubversion_repository.deleted = 0';
		if (!empty($addWhere)) {
			$where .= ' ' . $addWhere;
		}
		$queryArray = array(
			'SELECT' => 'tx_npsubversion_workingcopy.uid, wc_title, repository, wc_url, wc_type, wc_target_type, wc_extension, wc_extension_type, wc_path, wc_no_backup, rep_title, rep_url, rep_username, rep_password',
			'FROM' => 'tx_npsubversion_workingcopy LEFT JOIN tx_npsubversion_repository ON tx_npsubversion_workingcopy.repository = tx_npsubversion_repository.uid',
			'WHERE' => $where,
			'LIMIT' => $limit,
			'ORDERBY' => 'wc_target_type, wc_title',
		);
		#debug($queryArray);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArray);
		if ($res === FALSE) {
			return FALSE;
		}
		$workingcopies = array();
		while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if(t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
				$workingcopyClassName = t3lib_div::makeInstanceClassName('tx_npsubversion_workingcopy');
				$workingcopy = new $workingcopyClassName($row);
				$workingcopies[] = $workingcopy;
			} else {
		    	$workingcopies[] = t3lib_div::makeInstance('tx_npsubversion_workingcopy', $row);
			}
		}
		return $workingcopies;
	}

	/**
	 * Wrapper for t3lib_DB::fullQuoteStr()
	 *
	 * @param string $value string to be escaped
	 * @return string escaped string
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function fullQuoteString($value) {
		return $GLOBALS['TYPO3_DB']->fullQuoteStr($value, 'tx_npsubversion_repository');
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_model.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_model.php']);
}

?>