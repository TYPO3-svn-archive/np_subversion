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

require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_filestatus.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_div.php');

/**
 * This class abstracts access to the svn command line client
 * it must be instantiated in order to set default properties like path to svn command line tool and config directory
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_svn {

	/**
	 * @var string absolute path to the svn command line executable
	 */
	protected $svnPath = '/usr/local/bin/svn';

	/**
	 * @var string absolute path to the svn config directory (optional)
	 */
	protected $svnConfigDir = '';

	/**
	 * @var string umask mode to be applied after write access 
	 */
	protected $umask = '';

	/**
	 * @var array output of the last exec() call. One entry per line.
	 */
	protected $output = array();

	/**
	 * @var integer status of the last exec() call. 0 = no error.
	 */
	protected $status = 0;

	/**
	 * @var array cache for the file status
	 */
	protected $filestatusCache = array();

	/**
	 * @var array containing instances of hook classes
	 */
	protected $hookObjects = array();

	/**
	 * Getter for the path to the Subversion command line client
	 *
	 * @return string absolute path to the svn command line executable
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getSvnPath() {
		return $this->svnPath;
	}

	/**
	 * Sets the absolute path to the Subversion command line client
	 * On Windows this could be something like "C:/Program Files/Subversion/bin/svn.exe"
	 *
	 * @param string $svnPath absolute path to the svn command line executable
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function setSvnPath($svnPath) {
		$this->svnPath = $svnPath;
	}

	/**
	 * Getter for the Subversion Configuration directory
	 *
	 * @return string absolute path to config dir
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getSvnConfigDir() {
		return $this->svnConfigDir;
	}

	/**
	 * By default Subversion stores configuration globally in ".subversion" in the users home directory
	 * You can change this by setting the path here explicitly
	 *
	 * @param string absolute path to the svn configuration directory
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function setSvnConfigDir($svnConfigDir) {
		$this->svnConfigDir = $svnConfigDir;
	}

	/**
	 * Getter for the umask field
	 *
	 * @return string current UMASK mode
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getUmask() {
		return $this->umask;
	}

	/**
	 * Sets the UMASK (user file creation mode mask) mode for all svn interaction
	 * This is a string, because file modes can start with a zero
	 *
	 * @param string UMASK mode
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function setUmask($value) {
		$this->umask = $value;
	}

	/**
	 * Returns response of the Subversion command line client as an Array, one entry per line
	 *
	 * @return array lines returned from svn client
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getOutput() {
		return $this->output;
	}

	/**
	 * Returns response of the Subversion command line client as a string with a line feed character (chr 10) after each line
	 *
	 * @return string lines returned from svn client
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getOutputString() {
		return implode(chr(10), $this->output);
	}

	/**
	 * Similar to getOutputString() but adds a line break (<br />) to the end of each line instead of line feed character (chr 10)
	 *
	 * @return array lines returned from svn client
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getOutputHTML() {
		return implode('<br />', $this->output);
	}

	/**
	 * Returns the status of the last exec() call
	 * This is set by the PHP method exec. 0 = no errors
	 *
	 * @return int Status of the last exec call
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Constructor
	 *
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function __construct() {
		$this->initHookObjects();
	}

	/**
	 * Main method of this class.
	 * This constructs the PHP exec call and passes specified arguments, switches and the Subversion command to the command line client
	 * Currently parameters are not properly checked for malicious input. As only admins can specify altering parameters (svnConfigDir, svnPath, umask) this might be negligible though. Just be aware of the fact!
	 *
	 * @param string $svnCommand Subversion command ("update", "commit", "export", ...)
	 * @param array $arguments Subversion arguments (pathes, URLs)
	 * @param array $switches Subversion switches ("--encoding", "--file", ...)
	 * @return boolean TRUE on success (status == 0), otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function exec($svnCommand, array $arguments, array $switches = array()) {
		$this->output = array();
		$this->status = null;
		
			// replace password before calling pre-exec hook
		$password = NULL;
		if (isset($switches['password'])) {
			$password = $switches['password'];
			$switches['password'] = '**********';
		}
		$cancelled = FALSE;
		$abortMessage = '';
			// trigger pre-exec hooks and abort execution if $cancelled is set
		foreach($this->hookObjects as $hookObject) {
			$hookObject->exec_preProcess($svnCommand, $arguments, $switches, $cancelled, $abortMessage);
			if ($cancelled === TRUE) {
				if ($abortMessage === '') {
					$abortMessage = 'execution of "' . $svnCommand . '" has been cancelled by hook " ' . get_class($hookObject) . '"';
				}
				$this->output = array($abortMessage);
				return FALSE;
			}
		}
			// restore password
		if ($password !== NULL) {
			$switches['password'] = $password;
		}

		$_switches = '';
			// config-dir
		if (strlen($this->svnConfigDir) > 0 && !array_key_exists('config-dir', $switches)) {
			$switches['config-dir'] = $this->svnConfigDir;
		}

		foreach ($switches as $switch => $value) {
			$_switches .= ' --' . $switch;
			if ($value !== TRUE) {
				$_switches .= ' "' . $value . '"';
			}
		}

		$cmd = $this->svnPath . ' ' . $svnCommand . ' ' . $_switches;
		foreach($arguments as $argument) {
			$cmd .= ' ' . escapeshellarg($argument);
		}

			// set umask
		if (strlen($this->umask) > 0) {
			$oldumask = umask($this->umask);
		}

		// @todo: in some cases exec only returns one single line, we need one line per file.
		// So we're creating a temp file with the output when on Windows. There must be a better way.
		if (TYPO3_OS === 'WIN') {
			$execTempFile = t3lib_div::tempnam('np_subversion');
			$out = array();
			exec($cmd . ' >' . $execTempFile.' 2>&1', $out, $this->status);
			$this->output = file($execTempFile, FILE_IGNORE_NEW_LINES);
			@unlink($execTempFile);
		} else {
			exec($cmd . ' 2>&1', $this->output, $this->status);
		}

			// restore umask
		if (isset($oldumask)) {
			 umask($oldumask);
		}

			// replace password before calling post-exec hook
		if (isset($switches['password'])) {
			$switches['password'] = '**********';
		}
			// trigger post-exec hooks
		foreach($this->hookObjects as $hookObject) {
			$hookObject->exec_postProcess($svnCommand, $arguments, $switches);
		}

		return $this->status === 0;
	}

	/**
	 * Performs a quick check on the specified path to find out whether it belongs to a Subversion working copy
	 * This simply checks for the existence of an .svn-directory
	 *
	 * @param string $path absolute path without trailing slash, can be a file or folder
	 * @return boolean TRUE if .svn directory was found within the current folder, otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function isWorkingCopy($path) {
		if (!is_dir($path)) {
			$path = dirname($path);
		}
		if (empty($path) || !is_dir($path)) {
			return FALSE;
		}

		return is_dir(tx_npsubversion_div::combinePaths($path, '.svn'));
	}

	/**
	 * Calls _getFileStatus() if status has not been fetched before and stored in the internal status cache
	 * @see _getFileStatus()
	 *
	 * @param string $path absolute path to folder/file within a working copy
	 * @return misc FALSE (no working copy), "modified", "conflict", "added", "deleted", ...
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getFileStatus($path) {
			// path in Cache?
		if (isset($this->filestatusCache[$path])) {
			return $this->filestatusCache[$path];
		}

		$this->filestatusCache[$path] = $this->_getFileStatus($path);
		return $this->filestatusCache[$path];
	}

	/**
	 * Calls getFileStatusArray() to retrieve file status returned from "svn -status"
	 * Translates cryptic flags to human readable status and stores them in internal status cache
	 * @see getFileStatusArray()
	 *
	 * @param string $path absolute path to folder/file within a working copy
	 * @return misc FALSE (no working copy), "modified", "conflict", "added", "deleted", ...
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function _getFileStatus($path) {
		$path = tx_npsubversion_div::stripTrailingSlash($path);
		$filestatusArray = $this->getFileStatusArray($path);
			// no working copy / error -> cancel
		if ($filestatusArray === FALSE) {
			return FALSE;
		}
		if (count($filestatusArray) === 0) {
			return 'workingcopy';
		}
		$filesModified = FALSE;
		foreach($filestatusArray as $fileName => $filestatus) {
			switch ($filestatus->getTextStatus()) {
				case 'C':
				case 'M':
				case 'A':
				case 'D':
				case '?':
					$filesModified = TRUE;
					break;
			}
			$this->filestatusCache[$fileName] = $filestatus->getTextStatusLabel();

			if ($filestatus->getLockStatus() !== ' ') {
				if (strlen($this->filestatusCache[$fileName]) === 0) {
					$this->filestatusCache[$fileName] = 'locked';
				}
			}
		}
		if (isset($this->filestatusCache[$path])) {
			return $this->filestatusCache[$path];
		}
		if ($filesModified && is_dir($path)) {
			return 'modified';
		}
		return 'workingcopy';
	}

	/**
	 * Calls "svn -status" on the specified path and returns an array of tx_npsubversion_filestatus objects
	 * @see tx_npsubversion_filestatus
	 *
	 * @param string $path absolute path to folder/file within a working copy
	 * @param boolean $quiet if set, client print only essential information (see Subversion book)
	 * @return mixed array of tx_npsubversion_filestatus objects on success. Otherwise FALSE.
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getFileStatusArray($path, $quiet=FALSE) {
			// path is no workingcopy -> cancel
		if (!$this->isWorkingCopy($path)) {
			return FALSE;
		}
			// call svn status on file/folder
		$switches = array();
		if ($quiet) {
			$switches['quiet'] = TRUE;
		}
		$success = $this->exec('status', array($path), $switches);
		if (!$success) {
			return FALSE;
		}
		$filestatus = tx_npsubversion_filestatus::createFromSVNStatusArray($this->getOutput());
		return $filestatus;
	}

	/**
	 * Adds an overlay icon to the specified image by wrapping it with a div and placing the respective overlay icon over it via CSS
	 * We don't want to replace the original icon to support skins
	 *
	 * @param string $originalIcon path to the original icon
	 * @param string $overlayIconPath path to the overlay icon
	 * @param string $titleText title text of the resulting image
	 * @return string HTML code with the overlay icon added in a wrapping div container
	 */
	public function overlayIcon($originalIcon, $overlayIconPath, $titleText='') {
		$pattern = '/(<img\s+src="(.*?)"\s+width="(\d+?)"\s+height="(\d+?)".*\/>)/';
		$replacement = '<div style="width: $3px; height: $4px; float:left"><img src="' . $overlayIconPath . '" width="16" height="16" style="position: absolute; z-index: 1;" alt="" title="' . htmlspecialchars($titleText) . '" />$1</div>';

		return preg_replace($pattern, $replacement, $originalIcon);
	}

	/**
	 * Returns an array of files that were affected from the last Subversion interaction
	 *
	 * @return array list of affected files
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getAffectedPaths() {
		$paths = array();
		foreach($this->output as $line) {
			$path = trim(substr($line, 5));
			if (file_exists($path)) {
				$paths[] = $path;
			}
		}
		return $paths;
	}

	/**
	 * The status of the Subversion command line call is valid even if authentication failed.
	 * Thus we have to "parse" the output in order to find out whether authentication was successfull
	 * @todo: check whether this approach works for translated versions of the command line client
	 * 
	 * @return boolean TRUE if authentication failed, otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function authenticationFailed() {
		$matches = array();
		preg_match('/svn:.+authorization\sfailed/', $this->getOutputString(), $matches);
		if (count($matches) > 0) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Initialized the hook objects for this class.
	 * Each hook object has to implement the interface tx_npsubversion_commandHook.
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function initHookObjects() {
		$this->hookObjects = array();
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svnCommandHook'])) {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svnCommandHook'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['svnCommandHook'] as $classData) {
					$processObject = &t3lib_div::getUserObj($classData);

					if(!($processObject instanceof tx_npsubversion_svnCommandHook)) {
						throw new UnexpectedValueException('$processObject must implement interface tx_npsubversion_svnCommandHook', 1202072000);
					}

					$processObject->init($this);
					$this->hookObjects[] = $processObject;
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_svn.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_svn.php']);
}

?>
