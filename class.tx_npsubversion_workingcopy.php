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

require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_div.php');

/**
 * Class representing a target definition record.
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_workingcopy {

	/**
	 * @var integer uid of the corresponding db record
	 */
	protected $uid = 0;

	/**
	 * @var string title of this working copy
	 */
	protected $title = '';

	/**
	 * @var string absolute URL of the currently selected file in the working copy
	 */
	protected $url = '';

	/**
	 * @var string absolute URL of the repository
	 */
	protected $repositoryUrl = '';

	/**
	 * @var string URL of the working copy relative to the repository root
	 */
	protected $workingCopyUrl = '';

	/**
	 * @var string default username for this working copy
	 */
	protected $username = '';

	/**
	 * @var string default password for this working copy
	 */
	protected $password = '';

	/**
	 * @var integer type of this record. 0 = working copy, 1 = export target
	 */
	protected $type = 0;

	/**
	 * @var integer target type. 0 = folder, 1 = extension
	 */
	protected $targetType = 0;

	/**
	 * @var integer extension type (only applies if targetType = 1). 0 = local, 1 = global, 2 = system
	 */
	protected $extensionType = 0;

	/**
	 * @var string extension key (only applies if targetType = 1).
	 */
	protected $extensionKey = '';

	/**
	 * @var string local path of the working copy
	 */
	protected $path;

	/**
	 * @var string absolute local path of the working copy
	 */
	protected $absolutePath = NULL;

	/**
	 * @var string local path of the currently selected file/folder in the working copy
	 */
	protected $currentPath = NULL;

	/**
	 * @var boolean create backups for exports?
	 */
	protected $noBackup = FALSE;

	/**
	 * @return string
	 */
	public function getExtensionKey() {
		return $this->extensionKey;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getRepositoryUrl() {
		return $this->repositoryUrl;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return integer
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return integer
	 */
	public function getTargetType() {
		return $this->targetType;
	}

	/**
	 * @return integer
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return void
	 */
	public function selectFile($path) {
		$this->url = tx_npsubversion_div::combinePaths($this->url, $path);
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @return boolean
	 */
	public function isFolder() {
		return $this->targetType === 0;
	}

	/**
	 * @return boolean
	 */
	public function isExtension() {
		return $this->targetType === 1;
	}

	/**
	 * @return boolean
	 */
	public function shouldCreateBackup() {
		return !$this->noBackup;
	}

	/**
	 * @return boolean
	 */
	public function hasCredentials() {
		return $this->username !== '';
	}

	/**
	 * Setter for the current path
	 *
	 * @param string $currentPath absolute path to a file/folder within an existing working copy
	 * @return void
	 * @see tx_nsubversion_cm1
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function setCurrentPath($currentPath) {
		$this->currentPath = tx_npsubversion_div::stripTrailingSlash($currentPath);
	}

	/**
	 * Constructor. Creates a object representing a target definition record.
	 * Called from tx_npsubversion_model to create an instance based on a row in the database or from tx_nsubversion_cm1 to create an instance based on xml returned from "svn status"-call
	 *
	 * @param array $row associative array containig the database record fields
	 * @see tx_npsubversion_model
	 * @see tx_nsubversion_cm1
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function __construct($row) {
		$this->repositoryUrl = $row['rep_url'];
		$this->workingCopyUrl = $row['wc_url'];
		$this->url = tx_npsubversion_div::combinePaths($this->repositoryUrl, $this->workingCopyUrl);

		if (isset($row['absolutePath'])) {
			$this->absolutePath = $row['absolutePath'];
		} else {
			$this->path = $row['wc_path'];
		}

		if (isset($row['uid'])) {
			$this->uid = (integer)$row['uid'];
			$this->title = $row['wc_title'];

			$this->username = $row['rep_username'];
			$this->password = $row['rep_password'];
			$this->type = (integer)$row['wc_type'];
			$this->targetType = (integer)$row['wc_target_type'];
			$this->extensionKey = $row['wc_extension'];
			$this->extensionType = (integer)$row['wc_extension_type'];
			$this->noBackup = (boolean)$row['wc_no_backup'];
		}
	}

	/**
	 * Returns absolute path to the root of the working copy/export target no matter whether it's a folder or an extension
	 *
	 * @return string absolute path to working copy/export target
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getAbsolutePath() {
		if ($this->absolutePath !== NULL) {
			return $this->absolutePath;
		}
			// folder
		if ($this->targetType === 0) {
			$fdir = PATH_site.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];	// fileadmin dir, absolute
			$this->absolutePath = $fdir.$this->path;

			// extension
		} else if ($this->targetType === 1) {
			switch ($this->extensionType) {
					// global extension
				case 1:
					$extdir = PATH_site.TYPO3_mainDir.'ext/';
					break;

					// system extension
				case 2:
					$extdir = PATH_site.TYPO3_mainDir.'sysext/';
					break;

					// local extension
				default:
					$extdir = PATH_typo3conf.'ext/';
			}

			$this->absolutePath = $extdir.$this->extensionKey;
		}
			// we don't like backslashes
		$this->absolutePath = t3lib_div::fixWindowsFilePath($this->absolutePath);
		return $this->absolutePath;
	}

	/**
	 * When the current instance was not created from the database but from clicking a folder/file within a working copy in the file list module, currentPath points to the absolute location of this folder/file
	 *
	 * @return string absolute path of the currently selected folder/file in the working copy
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getCurrentPath() {
		if ($this->currentPath === NULL) {
			return $this->getAbsolutePath();
		}
		return $this->currentPath;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_workingcopy.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_workingcopy.php']);
}

?>
