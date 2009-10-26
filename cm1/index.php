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

unset($MCONF);
require_once('conf.php');
require_once($GLOBALS['BACK_PATH'] . 'init.php');
require_once($GLOBALS['BACK_PATH'] . 'template.php');
$LANG->includeLLFile('EXT:np_subversion/cm1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');

require_once(t3lib_extMgm::extPath('cms') . 'tslib/class.tslib_content.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_svn.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_model.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_filestatus.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_div.php');
require_once(t3lib_extMgm::extPath('np_subversion') . 'class.tx_npsubversion_diffparser.php');

/**
 * Main class of np_subversion.
 * Displays the backend module and handles interaction between SVN repository
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_nsubversion_cm1 extends t3lib_SCbase {

	/**
	 * Extension configuration
	 *
	 * @var array
	 */
	protected $conf;

	/**
	 * @var tx_npsubversion_svn
	 */
	protected $svn;

	/**
	 * @var tx_npsubversion_model
	 */
	protected $model;

	/**
	 * The command, that was passed to the script (commit, update, ...)
	 *
	 * @var string
	 */
	protected $cmd;

	/**
	 * module parameters (similar to piVars in plugins)
	 *
	 * @var array
	 */
	protected $modVars;

	/**
	 * Current working copy
	 *
	 * @var tx_npsubversion_workingcopy
	 */
	protected $workingCopy;

	/**
	 * module HTML template
	 *
	 * @var string
	 */
	protected $templateCode;

	/**
	 * backup path for exports
	 *
	 * @var string
	 */
	protected $backupPath;

	/**
	 * np_subversion Cookies
	 *
	 * @var array
	 */
	protected $cookieData = NULL;

	/**
	 * Initialization method.
	 * Will be called before anything else happens, sets up class fields.
	 * Dies if no valid working copy was passed to the script (see initWorkingCopy())
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function init() {
			// set internal properties
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['np_subversion']);
		$this->svn = t3lib_div::makeInstance('tx_npsubversion_svn');
		$this->svn->setSvnPath($this->conf['svn_path']);
		$this->svn->setSvnConfigDir($this->conf['svn_config_dir']);
		$this->svn->setUmask($this->conf['umask']);
		$this->svn->setUsePassthru($this->conf['use_passthru']);
		$this->svn->setCommandSuffix($this->conf['command_suffix']);

		$this->model = t3lib_div::makeInstance('tx_npsubversion_model');

		$this->cmd = t3lib_div::_GP('cmd');
		$this->modVars = t3lib_div::GPvar('modVars');

			// if no working copy record can be acquired from the arguments passed to this module, we'll commit suicide
		$initSuccess = $this->initWorkingCopy();
		if ($initSuccess === FALSE || !$this->validateWorkingCopy()) {
			die('invalid working copy!');
		}

			// load HTML template for the backend module views
		$this->templateCode = file_get_contents(t3lib_extMgm::extPath('np_subversion') . 'cm1/tx_npsubversion_cm1.html');

		parent::init();
	}

	/**
	 * This method is called from tx_nsubversion_cm1::init().
	 * It tries to retrieve a working copy record from arguments wc/id:
	 * if $_GET['wc'] (uid of the working copy record) is given respective, record will be loaded from the DB
	 * if $_GET['path'] (absolute path coming from the filelist module) is given, first record matching will be loaded from DB (see tx_npsubversion_model::getByPath)
	 *
	 * @return boolean TRUE if creation of $this->workingCopy succeeded, otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function initWorkingCopy() {
			// working copy id given -> load working
		if (strlen(t3lib_div::_GP('wc')) > 0) {
			$this->workingCopy = $this->model->getByUid(t3lib_div::_GP('wc'));
			if ($this->workingCopy === FALSE) {
				return FALSE;
			}
			if (strlen(t3lib_div::_GP('path')) > 0 ) {
				$path = urldecode(t3lib_div::_GP('path'));
				$relativePath = tx_npsubversion_div::stripTrailingSlash(substr($path, strlen($this->workingCopy->getAbsolutePath())));
				$this->workingCopy->setCurrentPath($path);
				$this->workingCopy->selectFile($relativePath);
			}
			return TRUE;
		}

			// no working copy id, no path -> error
		if (strlen(t3lib_div::_GP('path')) === 0) {
			return FALSE;
		}

			// local path
		$path = urldecode(t3lib_div::_GP('path'));
		$relativePath = tx_npsubversion_div::stripTrailingSlash(substr($path, strlen(PATH_site . 'fileadmin/')));

			// lookup working copy by path
		$this->workingCopy = $this->model->getByPath($relativePath);
		if ($this->workingCopy === FALSE) {
			return FALSE;
		}

		$this->workingCopy->setCurrentPath($path);
		$rootPath = $this->workingCopy->getAbsolutePath();
		$relativePath = tx_npsubversion_div::stripTrailingSlash(substr($path, strlen($rootPath)));
		$this->workingCopy->selectFile($relativePath);

		return TRUE;
	}

	/**
	 * Checks wheter $this->workingCopy is valid
	 *
	 * @return boolean TRUE if working copy is valid, otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function validateWorkingCopy() {
		if ($this->workingCopy->isExtension()) {
			$extensionKey = $this->workingCopy->getExtensionKey();
			if (trim($extensionKey) === '') {
				return FALSE;
			}
		} elseif ($this->workingCopy->isFolder()) {
			$path = $this->workingCopy->getPath();
			if (trim($path) === '') {
				return FALSE;
			}
		}
		if (!t3lib_div::isAllowedAbsPath($this->workingCopy->getAbsolutePath())) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Main function of the module. Taken over from extension kickstarter, with subtle adaptions.
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function main() {
			// for security reasons only admins can use this module. This might be changed in future versions!
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			die('access denied');
		}

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->styleSheetFile2 = t3lib_extMgm::extRelPath('np_subversion') . 'cm1/styles.css';
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form='<form action="" method="POST">';

			// JavaScript
		$this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}
			</script>
		';
		$this->doc->JScode .= '<script language="javascript" type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('np_subversion') . 'res/prototype.js"></script>';
		$this->doc->JScode .= '<script language="javascript" type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('np_subversion') . 'cm1/scripts.js"></script>';
		if ($this->cmd === 'diff') {
			$this->doc->JScode .= '<script language="javascript" type="text/javascript" src="'. $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('np_subversion') . 'cm1/diff.js"></script>';
		}
		$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
			</script>
		';

		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->section('', $this->getHeaderSubpart());

		// Render content:
		$this->moduleContent();
	}

	/**
	 * Ouputs the module content. Taken over from extension kickstarter.
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * This method acts like a dispatcher and calls functions depending on the Subversion command passed to the module
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function moduleContent()	{
		switch ($this->cmd) {
			case 'commit':
				$header = 'COMMIT';
				$content = $this->commit();
				break;
			case 'update':
				$header = 'UPDATE';
				$content = $this->update();
				break;
			case 'export':
				$header = 'EXPORT';
				$content = $this->export();
				break;
			case 'checkout':
				$header = 'CHECKOUT';
				$content = $this->checkout();
				break;
			case 'diff':
				$content = $this->diff();
				break;
			case 'delete':
				$header = 'DELETE';
				$content = $this->delete();
				break;
			case 'revert':
				$header = 'REVERT';
				$content = $this->revert();
				break;
			case 'log':
				$header = 'LOG';
				$content = $this->log();
				break;
			default:
				$header = 'COMMAND NOT ALLOWED';
				$content = '<em>the command "' . htmlspecialchars($this->cmd) . '" is not implemented</em>';
				break;
		}

		$this->content .= $this->doc->section($header, $content, 0, 1);
	}

	/**
	 * When the commit-method is called it displays an intermediate view (commitPreview) to allow the user to review and adapt settings
	 * only if preview is accepted ($_GET['ok'] is set) this method will continue and process the commit command:
	 * Files that were marked for inclusion are collected and passed on to the SVN client. If a file is not yet versioned or marked for deletion, an additional SVN call is made to add/delete this file
	 *
	 * @return string preview/failure/summary view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function commit() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->commitPreview();
		}
		if (empty($this->modVars['include'])) {
			return $this->commitPreview($GLOBALS['LANG']->getLL('no_files_marked'));
		}

		$filestatusArray = $this->svn->getFileStatusArray($this->workingCopy->getAbsolutePath());
		if ($filestatusArray === FALSE) {
			return $this->commitPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		$addFiles = array();
		$deleteFiles = array();
		$allFiles = array();
		foreach($this->modVars['include'] as $file) {
			$file = urldecode($file);
			if (!array_key_exists($file, $filestatusArray)) {
				$errorMessage = sprintf($GLOBALS['LANG']->getLL('file_not_in_workingcopy'), htmlentities($file));
				return $this->commitPreview($errorMessage);
			}
			switch ($filestatusArray[$file]->getTextStatus()) {

					// non-versioned
				case '?':
					$addFiles[] = $file;
					break;

					// missing
				case '!':
					$deleteFiles[] = $file;
					break;
			}
			$allFiles[] = $file;
		}

		if (count($addFiles) > 0) {
			$this->svn->exec('add', $addFiles);
		}

		if (count($deleteFiles) > 0) {
			$this->svn->exec('delete', $deleteFiles);
		}

		$switches = array('force-log' => TRUE);

		$msgTempFile = t3lib_div::tempnam('np_subversion');
		$msgTempFileSuccess = t3lib_div::writeFile($msgTempFile, $this->modVars['message']);
		if ($msgTempFileSuccess === FALSE) {
			return $this->commitPreview($GLOBALS['LANG']->getLL('error_log_message_file'));
		}
		$switches['file'] = $msgTempFile;

		$this->addAuthenticationSwitches($switches);

		if (strlen($this->conf['svn_encoding']) > 0) {
			$switches['encoding'] = $this->conf['svn_encoding'];
		} else if (strlen($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])) {
			$switches['encoding'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
		}
		$this->svn->exec('commit', $allFiles, $switches);

		@unlink($msgTempFile);

		if ($this->svn->authenticationFailed()) {
			return $this->commitPreview($GLOBALS['LANG']->getLL('authentication_failed'));
		}

		if ($this->svn->getStatus() !== 0) {
			return $this->commitPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		$this->saveLogMessageToHistory($this->modVars['message']);

		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_COMMIT###');

		$rowTemplate = tslib_cObj::getSubpart($content, '###SUBPART_ROW###');
		$rows = '';
		foreach ($this->svn->getOutput() as $line) {
			$separatorPosition = strpos($line, ' ');
			$status = trim(substr($line, 0, $separatorPosition));
			$message = trim(substr($line, $separatorPosition+1));

			switch ($status) {
				case 'Adding':
					$action = 'Adding';
					$cssClass = 'added';
					break;
				case 'Deleting':
					$action = 'Deleting';
					$cssClass = 'deleted';
					break;
				case 'svn:':
					$action = 'Error';
					$cssClass = 'error';
					break;
				default:
					$action = $status;
					$cssClass = '';
			}

			$rowMarkerArray['###CSS_CLASS###'] = $cssClass;
			$rowMarkerArray['###ACTION###'] = $action;
			$rowMarkerArray['###MESSAGE###'] = $message;

			$rows .= tslib_cObj::substituteMarkerArray($rowTemplate, $rowMarkerArray);
		}
		$content = tslib_cObj::substituteSubpart($content, 'SUBPART_ROW', $rows);
		$this->requestNavFrameReload();
		return $content;
	}

	/**
	 * This method is called from commit() to allow the user to authenticate and mark files for inclusion/exclusion and to display error messages
	 *
	 * @param string $errorMessage if not null, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string commit "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function commitPreview($errorMessage = NULL) {
		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_COMMIT###');
		$markerArray = array();

		$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();

		$filestatusArray = $this->svn->getFileStatusArray($this->workingCopy->getCurrentPath());
		if ($filestatusArray === FALSE) {
			return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('subversion_error') . ': '  . $this->svn->getOutputHTML() . '</div>';
		}

		if (count($filestatusArray) === 0) {
			return '<div class="infobox">' . $GLOBALS['LANG']->getLL('no_files_changed') . '</div>';
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###LOG_MESSAGE_SELECTOR###'] = $this->logMessageSelector();
		$markerArray['###LOG_MESSAGE###'] = stripslashes(htmlspecialchars($this->modVars['message']));

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="id" value="' . urlencode($this->workingCopy->getAbsolutePath()) . '" />
			<input type="hidden" name="cmd" value="commit" />';

		$fileTemplate = tslib_cObj::getSubpart($content, '###SUBPART_FILE###');
		$fileList = '';
		$currentDir = tx_npsubversion_div::addTrailingSlash($this->workingCopy->getCurrentPath());
		foreach($filestatusArray as $filePath => $filestatus) {
			$filePathRelative = substr($filePath, strlen($currentDir));
			$pathInfo = pathinfo($filePath);
			$fileExtension = (strlen($pathInfo['extension']) > 0) ? '.' . trim($pathInfo['extension']) : '';
			$fileTextStatus = $filestatus->getTextStatusLabel();
			$filePropertyStatus = $filestatus->getPropertyStatusLabel();

			$fileIcon = t3lib_BEfunc::getFileIcon(substr($fileExtension,1));
			$fileIcon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/fileicons/' . $fileIcon,'width="18" height="16"') . ' title="' . htmlspecialchars($filePath) . '" alt="" />';

			$fileLock = $filestatus->getLockStatusLabel();
			$textStatus = $filestatus->getTextStatus();
			$checked = ($textStatus === 'A' || $textStatus === 'D' || $textStatus === 'M' || $fileLock);

			if ($fileTextStatus === 'modified') {
				$fileDiffIcon = '<img src="../res/icons/diff.gif" width="16" height="16" border="0" align="top" />';
				$fileDiffIcon = '<a href="#" onclick="diff(\'' . urlencode($filePath) . '\', ' . $this->workingCopy->getUid() . '); return false">' . $fileDiffIcon . '</a>';
			} else {
				$fileDiffIcon = '&nbsp;';
			}

			$fileMarkerArray = array();
			$checkboxId = 'file_' . t3lib_div::shortMD5($filePath);
			$fileMarkerArray['###CHECKBOX###'] = '<input type="checkbox" name="modVars[include][]" id="' . $checkboxId . '" value="' . urlencode($filePath) . '"' . ($checked ? ' checked="checked"' : '') . ' onclick="return false" />';
			$fileMarkerArray['###CHECKBOX_ID###'] = $checkboxId;
			$fileMarkerArray['###FILE_ICON###'] = $fileIcon;
			$fileMarkerArray['###FILE_PATH###'] = $filePath ? $filePath : '&nbsp;';
			$fileMarkerArray['###FILE_PATH_RELATIVE###'] = $filePathRelative ? $filePathRelative : '&nbsp;';
			$fileMarkerArray['###FILE_EXTENSION###'] = $fileExtension ? $fileExtension : ' - ';
			$fileMarkerArray['###FILE_TEXT_STATUS###'] = $fileTextStatus ? $fileTextStatus : '&nbsp;';
			$fileMarkerArray['###FILE_PROPERTY_STATUS###'] = $filePropertyStatus ? $filePropertyStatus : '&nbsp;';
			$fileMarkerArray['###FILE_LOCK###'] = $fileLock ? $fileLock : '&nbsp;';
			$fileMarkerArray['###FILE_DIFF_ICON###'] = $fileDiffIcon;

			$fileList .= tslib_cObj::substituteMarkerArray($fileTemplate, $fileMarkerArray);
		}
		$content = tslib_cObj::substituteSubpart($content, 'SUBPART_FILE', $fileList);
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * Processes Subversion update command and outputs summary or authorization view if authentication failed
	 *
	 * @return string summary/authentication view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function update() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->updatePreview();
		}
		$markerArray = array();
		$args = array($this->workingCopy->getCurrentPath());
		$switches = array();
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('update', $args, $switches);

		if ($this->svn->authenticationFailed()) {
			return $this->updatePreview($GLOBALS['LANG']->getLL('authentication_failed'));
		}

		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}
		if (count($this->svn->getOutput()) === 0) {
			return $this->updatePreview('<a href="#" onclick="top.goToModule(\'tools_em\', 0, \'CMD[showExt]=np_subversion&SET[singleDetails]=info\');this.blur();return false;">' . $GLOBALS['LANG']->getLL('no_output') . '</a>');
		}

		$this->setPermissions($this->svn->getAffectedPaths());

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_UPDATE###');
		$markerArray['###ROWS###'] = $this->getStatusRowsSubpart();
		$this->requestNavFrameReload();
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * This method is called from update() to allow the user to authenticate
	 *
	 * @param string $errorMessage if not NULL, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string update "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function updatePreview($errorMessage = NULL) {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_UPDATE###');
		$markerArray = array();
		$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();

		if (!$this->svn->isWorkingCopy($this->workingCopy->getAbsolutePath())) {
			return '<div class="errorbox">' . sprintf($GLOBALS['LANG']->getLL('no_working_copy'), $this->truncatePath($this->workingCopy->getAbsolutePath())) . '</div>';
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="wc" value="' . urlencode($this->workingCopy->getUid()) . '" />
			<input type="hidden" name="cmd" value="update" />';

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * Processes Subversion checkout command and outputs summary or authorization view if authentication failed
	 *
	 * @return string summary/authentication view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function checkout() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->checkoutPreview();
		}
		$markerArray = array();
		$args = array($this->workingCopy->getUrl(), $this->workingCopy->getAbsolutePath());
		$switches = array();
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('checkout', $args, $switches);

		if ($this->svn->authenticationFailed()) {
			return $this->checkoutPreview($GLOBALS['LANG']->getLL('authentication_failed'));
		}
		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}
		if (count($this->svn->getOutput()) === 0) {
			return $this->checkoutPreview('<a href="#" onclick="top.goToModule(\'tools_em\', 0, \'CMD[showExt]=np_subversion&SET[singleDetails]=info\');this.blur();return false;">' . $GLOBALS['LANG']->getLL('no_output') . '</a>');
		}
			// set file permissions and reload navigation frame
		$this->setPermissions($this->svn->getAffectedPaths());

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_CHECKOUT###');
		$markerArray['###ROWS###'] = $this->getStatusRowsSubpart();
		$this->requestNavFrameReload();
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * This method is called from checkout() to allow the user to authenticate
	 *
	 * @param string $errorMessage if not NULL, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string checkout "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function checkoutPreview($errorMessage = NULL) {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_CHECKOUT###');
		$markerArray = array();
		$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();

		$path = $this->workingCopy->getAbsolutePath();
		if (strlen($path) === 0) {
			return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('no_checkout_path') . '</div>';
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="wc" value="' . urlencode($this->workingCopy->getUid()) . '" />
			<input type="hidden" name="cmd" value="checkout" />';

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * When the export-method is called it displays an intermediate view (exportPreview) to allow the user to review and adapt settings
	 * only if preview is accepted ($_GET['ok'] is set) this method will continue and process the export command:
	 * This exports all contents of the working copy to a temporary directory and renames the target folder only when export finishes successfully
	 *
	 * @return string preview/failure/summary view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function export() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->exportPreview();
		}
		require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');
		$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		if (!$fileFunc->is_directory($this->workingCopy->getAbsolutePath())) {
			$mkDirSuccess = t3lib_div::mkdir($this->workingCopy->getAbsolutePath());
			if ($mkDirSuccess !== TRUE) {
				return '<div class="errorbox">Could not create target target path at "' . $this->truncatePath($this->workingCopy->getAbsolutePath()) . '".</div>';
			}
		}
		$tempPath = (string)$fileFunc->getUniqueName('_tmp', $this->workingCopy->getAbsolutePath());
		if ($tempPath === '') {
			return '<div class="errorbox">' . sprintf($GLOBALS['LANG']->getLL('target_path_does_not_exist'), $this->truncatePath($this->workingCopy->getAbsolutePath())) . '</div>';
		}
		$mkDirSuccess = t3lib_div::mkdir($tempPath);
		if ($mkDirSuccess !== TRUE) {
			return '<div class="errorbox">' . sprintf($GLOBALS['LANG']->getLL('error_creating_temp_folder'), $this->truncatePath($tempPath)) . '</div>';
		}

		$args = array($this->workingCopy->getUrl(), $tempPath);
		$switches = array('force' => TRUE);
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('export', $args, $switches);

		if ($this->svn->authenticationFailed()) {
			return $this->exportPreview($GLOBALS['LANG']->getLL('authentication_failed'));
		}

			// SVN error occured -> go back to preview
		if ($this->svn->getStatus() !== 0) {
			return $this->exportPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		if (count($this->svn->getOutput()) === 0) {
			return $this->exportPreview('<a href="#" onclick="top.goToModule(\'tools_em\', 0, \'CMD[showExt]=np_subversion&SET[singleDetails]=info\');this.blur();return false;">' . $GLOBALS['LANG']->getLL('no_output') . '</a>');
		}

			// no affected files -> go back to preview
		if (count($this->svn->getAffectedPaths()) === 0) {
			return $this->exportPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

			// displays error message if target directory doesn't exist or backup could not be created
		if (is_dir($this->workingCopy->getAbsolutePath())) {
			if (!is_dir($this->getBackupRoot())) {
				$successBackupDir = @t3lib_div::mkdir($this->getBackupRoot());
				if (!$successBackupDir) {
					return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('error_creating_backup_folder') . '</div>';
				}
			}
			$successBackup = rename($this->workingCopy->getAbsolutePath(), $this->getBackupPath());
			if (!$successBackup) {
				return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('error_renaming_target_folder') . '</div>';
			}
		}
			// set file permissions
		$this->setPermissions($this->svn->getAffectedPaths());

		$successTemp = rename($tempPath, $this->workingCopy->getAbsolutePath());
		if (!$successTemp) {
			return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('error_renaming_temp_folder') . '</div>';
		}

		if (!$this->modVars['backup'] && is_dir($this->getBackupPath())) {
			tx_npsubversion_div::rmdir_recursive($this->getBackupPath());
		}

		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_EXPORT###');
		$markerArray['###ROWS###'] = $this->getStatusRowsSubpart();

		if ($this->modVars['backup']) {
			$markerArray['###BACKUP_PATH###'] = '<div class="infobox">' . sprintf($GLOBALS['LANG']->getLL('backup_stored_at'), $this->truncatePath($this->getBackupPath())) . '</div>';
		} else {
			$markerArray['###BACKUP_PATH###'] = '<div class="infobox">' . $GLOBALS['LANG']->getLL('no_backup_stored') . '</div>';
		}
		$this->requestNavFrameReload();
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * This method is called from export() to allow the user to authenticate and chose to create a backup of the target folder (if existent)
	 *
	 * @param string $errorMessage if not NULL, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string export "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function exportPreview($errorMessage = NULL) {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_EXPORT###');
		$markerArray = array();
		$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();

		if ($this->svn->isWorkingCopy($this->workingCopy->getAbsolutePath())) {
			return $this->exportPreview(sprintf($GLOBALS['LANG']->getLL('no_export_target'), $this->truncatePath($this->workingCopy->getAbsolutePath())));
		}

		if (file_exists($this->workingCopy->getAbsolutePath())) {
			$template = tslib_cObj::substituteSubpart($template, '###SUBPART_BACKUP###', tslib_cObj::getSubpart($template, '###SUBPART_BACKUP###'));
			$markerArray['###BACKUP_LABEL###'] = sprintf($GLOBALS['LANG']->getLL('backup_label'), $this->getBackupPathRelative());
			if ((!isset($this->modVars) && $this->workingCopy->shouldCreateBackup()) || $this->modVars['backup']) {
				$markerArray['###BACKUP_CHECKED##'] = 'checked="checked"';
			} else {
				$markerArray['###BACKUP_CHECKED###'] = '';
			}
		} else {
			$template = tslib_cObj::substituteSubpart($template, '###SUBPART_BACKUP###', '');
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="wc" value="' . urlencode($this->workingCopy->getUid()) . '" />
			<input type="hidden" name="cmd" value="export" />';

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * Processes Subversion diff command and shows comparison- or authorization view if authentication failed
	 *
	 * @return string comparison/authentication view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function diff() {
		$markerArray = array();
		$path = $this->workingCopy->getCurrentPath();

		$revisionInfos = (array)$this->getRevisions($path);
		$revision = (int)urldecode(t3lib_div::_GP('revision'));

		if ($this->svn->authenticationFailed()) {
			$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_AUTHENTICATION_FAILED###');
			$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();
			$markerArray['###HIDDENFIELDS###'] = '
				<input type="hidden" name="id" value="' . urlencode($path) . '" />
				<input type="hidden" name="cmd" value="diff" />
				<input type="hidden" name="revision" value="' . $revision . '" />';

			return tslib_cObj::substituteMarkerArray($content, $markerArray);
		}

		if (count($revisionInfos) < 2 || !array_key_exists($revision, $revisionInfos)) {
			$revision = 'HEAD';
		}

		$args = array($path);
		$switches = array('extensions' => '--ignore-eol-style', 'revision' => $revision);
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('diff', $args, $switches);

		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}

		$patch = $this->svn->getOutputString();
		$file = file_get_contents($path);
		$diffParser = t3lib_div::makeInstance('tx_npsubversion_diffparser');
		$diffParser->start($patch, $file);

		$diffRowDefaultTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_DIFF_ROW_DEFAULT###');
		$diffRowAddedTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_DIFF_ROW_ADDED###');
		$diffRowDeletedTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_DIFF_ROW_DELETED###');
		$diffRowInexistentTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_DIFF_ROW_INEXISTENT###');

		$file1Modifications = $diffParser->getFile1Modifications();
		$file1Rows = '';
		$modificationsJS = array();
		for ($i=0; $i < count($file1Modifications); $i++) {
			$markerArray = array();
			switch ($file1Modifications[$i]['type']) {
				case 'content':
					$rowTemplate = $diffRowDefaultTemplate;
					break;
				case 'deleted':
					$rowTemplate = $diffRowDeletedTemplate;
					$modificationsJS[] = '{line: ' . $file1Modifications[$i]['line'] . ', type: \'' . $file1Modifications[$i]['type'] . '\'}';
					break;
				case 'inexistent':
					$rowTemplate = $diffRowInexistentTemplate;
					break;
			}
			$markerArray['###LINENUMBER###'] = (integer)$file1Modifications[$i]['line'];
			$markerArray['###CONTENT###'] = strlen(trim($file1Modifications[$i]['content'])) ? htmlspecialchars($file1Modifications[$i]['content']) : '&nbsp;';
			$file1Rows .= tslib_cObj::substituteMarkerArray($rowTemplate, $markerArray);
		}

		$file2Modifications = $diffParser->getFile2Modifications();
		$file2Rows = '';
		for ($i=0; $i < count($file2Modifications); $i++) {
			$markerArray = array();
			switch ($file2Modifications[$i]['type']) {
				case 'content':
					$rowTemplate = $diffRowDefaultTemplate;
					break;
				case 'added':
					$modificationsJS[] = '{line: ' . $file2Modifications[$i]['line'] . ', type: \'' . $file2Modifications[$i]['type'] . '\'}';
					$rowTemplate = $diffRowAddedTemplate;
					break;
				case 'inexistent':
					$rowTemplate = $diffRowInexistentTemplate;
					break;
			}
			$markerArray['###LINENUMBER###'] = (integer)$file2Modifications[$i]['line'];
			$markerArray['###CONTENT###'] = strlen(trim($file2Modifications[$i]['content'])) ? htmlspecialchars($file2Modifications[$i]['content']) : '&nbsp;';
			$file2Rows .= tslib_cObj::substituteMarkerArray($rowTemplate, $markerArray);
		}
		$diffJS = 'var modifications = [' . implode(', ', $modificationsJS) . '];' . chr(10);
		$diffJS .= 'var lines = ' . count($file1Modifications) . ';' . chr(10);

		$content= tslib_cObj::getSubpart($this->templateCode, '###SUBPART_DIFF###');
		$markerArray['###FILENAME###'] = basename($path);
		$markerArray['###PATH###'] = htmlspecialchars(urldecode(t3lib_div::_GP('path')));
		$markerArray['###WC###'] = $this->workingCopy->getUid();
		$markerArray['###USERNAME###'] = htmlentities($this->modVars['username']);
		$markerArray['###PASSWORD###'] = htmlentities($this->modVars['password']);
		$markerArray['###REVISION_SELECTOR###'] = $this->revisionSelector($revisionInfos, $revision, TRUE);
		$markerArray['###HEADER_FILE1###'] = 'Working Base (revision: ' . $revision . ')';
		$markerArray['###HEADER_FILE2###'] = 'Working Copy';
		$markerArray['###FILES_IDENTICAL###'] = 'Files are identical!';
		$markerArray['###FILE1_ROWS###'] = $file1Rows;
		$markerArray['###FILE2_ROWS###'] = $file2Rows;

		$diffJS = '<script language="javascript" type="text/javascript">' . $diffJS . '</script>';

		return $diffJS . tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * Processes Subversion delete command
	 * When the delete-method is called it displays an intermediate view (deletePreview) to allow the user to confirm deletion.
	 * only if preview is accepted ($_GET['ok'] is set) this method will continue and process the commit command
	 *
	 * @return string summary view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 * @author Martin Kutschker <Martin.Kutschker@blackbox.net>
	 */
	protected function delete() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->deletePreview();
		}
		$path = $this->workingCopy->getCurrentPath();
		$markerArray = array();

		$args = array($path);
		$switches = array();
			// remove unversioned or modified files?
		if (t3lib_div::GPvar('force')) {
			$switches['force'] = TRUE;
		}

		$this->svn->exec('delete', $args, $switches);
		if ($this->svn->getStatus() !== 0) {
			return $this->deletePreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		$this->requestNavFrameReload();
		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_DELETE###');
		$statusRows = $this->getStatusRowsSubpart();
		if ($statusRows === '') {
			return '<div class="infobox">' . sprintf($GLOBALS['LANG']->getLL('file_deleted'), htmlentities(basename($path))) . '</div>';
		}
		$markerArray['###ROWS###'] = $statusRows;
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * This method is called from delete() to confirm deletion and to display error messages
	 *
	 * @param string $errorMessage if not null, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string delete "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function deletePreview($errorMessage = NULL) {
		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_DELETE###');
		$markerArray = array();
		$path = $this->workingCopy->getCurrentPath();
		if ($path === $this->workingCopy->getAbsolutePath()) {
			return '<div class="errorbox">You can\'t mark the root of your working copy deleted. Please remove this folder manually.</div>';
		}

		if (!file_exists($path)) {
			return '<div class="errorbox">' . sprintf($GLOBALS['LANG']->getLL('file_does_not_exist'), htmlspecialchars($path)) . '</div>';
		}
		$filestatusArray = $this->svn->getFileStatusArray($path);
		if ($filestatusArray === FALSE) {
			return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML() . '</div>';
		}
		$markerArray['###FILE_TYPE###'] = is_dir($path) ? 'directory' : 'file';
		$markerArray['###MODIFICATIONS###'] = count($filestatusArray) > 0 ? $GLOBALS['LANG']->getLL('note') . ': ' . $GLOBALS['LANG']->getLL('contains_local_modifications') : '';
		$markerArray['###FILE_PATH###'] = htmlspecialchars($this->truncatePath($path));

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="id" value="' . urlencode($this->workingCopy->getCurrentPath()) . '" />
			<input type="hidden" name="cmd" value="delete" />';

		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * Processes Subversion revert command
	 * When the revert-method is called it displays an intermediate view (revertPreview) to allow the user to mark files for inclusion/exclusion.
	 * only if preview is accepted ($_GET['ok'] is set) this method will continue and process the revert command
	 *
	 * @return string summary view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 * @author Martin Kutschker <Martin.Kutschker@blackbox.net>
	 */
	protected function revert() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->revertPreview();
		}
		if (empty($this->modVars['include'])) {
			return $this->revertPreview($GLOBALS['LANG']->getLL('no_files_marked'));
		}

		$filestatusArray = $this->svn->getFileStatusArray($this->workingCopy->getAbsolutePath());
		if ($filestatusArray === FALSE) {
			return $this->revertPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		$revertFiles = array();
		foreach($this->modVars['include'] as $file) {
			$file = urldecode($file);
			if (!array_key_exists($file, $filestatusArray)) {
				$errorMessage = sprintf($GLOBALS['LANG']->getLL('file_not_in_workingcopy'), htmlentities($file));
				return $this->revertPreview($errorMessage);
			}
			$revertFiles[] = $file;
		}

		$this->svn->exec('revert', $revertFiles);

		if ($this->svn->getStatus() !== 0) {
			return $this->revertPreview($GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML());
		}

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_REVERT###');

		$rowTemplate = tslib_cObj::getSubpart($content, '###SUBPART_ROW###');
		$rows = '';
		foreach ($this->svn->getOutput() as $line) {
			$separatorPosition = strpos($line, ' ');
			$status = trim(substr($line, 0, $separatorPosition));
			$message = trim(substr($line, $separatorPosition+1));

			$rowMarkerArray['###ACTION###'] = $status;
			$rowMarkerArray['###MESSAGE###'] = $message;

			$rows .= tslib_cObj::substituteMarkerArray($rowTemplate, $rowMarkerArray);
		}

		$content = tslib_cObj::substituteSubpart($content, 'SUBPART_ROW', $rows);

		$this->requestNavFrameReload();
		return $content;
	}

	/**
	 * This method is called from revert() to allow the user mark files for inclusion/exclusion and to display error messages
	 *
	 * @param string $errorMessage if not null, an error occured and marker ###ERROR### will be substituted with $errorMessage
	 * @return string commit "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function revertPreview($errorMessage = NULL) {
		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_REVERT###');
		$markerArray = array();

		$filestatusArray = $this->svn->getFileStatusArray($this->workingCopy->getCurrentPath());
		if ($filestatusArray === FALSE) {
			return '<div class="errorbox">' . $GLOBALS['LANG']->getLL('subversion_error') . ': ' . $this->svn->getOutputHTML() . '</div>';
		}
		if (count($filestatusArray) === 0) {
			return '<div class="infobox">' . $GLOBALS['LANG']->getLL('no_files_changed') . '</div>';
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="id" value="' . urlencode($this->workingCopy->getAbsolutePath()) . '" />
			<input type="hidden" name="cmd" value="revert" />';

		$fileTemplate = tslib_cObj::getSubpart($content, '###SUBPART_FILE###');
		$fileList = '';
		$currentDir = tx_npsubversion_div::addTrailingSlash($this->workingCopy->getCurrentPath());
		$hasNonVersionedFiles = FALSE;
		foreach($filestatusArray as $filePath => $filestatus) {
			$filePathRelative = substr($filePath, strlen($currentDir));
			$pathInfo = pathinfo($filePath);
			$fileExtension = (strlen($pathInfo['extension']) > 0) ? '.' . trim($pathInfo['extension']) : '';
			$fileTextStatus = $filestatus->getTextStatusLabel();
			if ($fileTextStatus === 'non-versioned') {
				$hasNonVersionedFiles = TRUE;
				continue;
			}
			$filePropertyStatus = $filestatus->getPropertyStatusLabel();
			$fileLock = $filestatus->getLockStatusLabel();

			$fileIcon = t3lib_BEfunc::getFileIcon(substr($fileExtension,1));
			$fileIcon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/fileicons/' . $fileIcon,'width="18" height="16"') . ' title="' . htmlspecialchars($filePath) . '" alt="" />';

			if ($fileTextStatus === 'modified') {
				$fileDiffIcon = '<img src="../res/icons/diff.gif" width="16" height="16" border="0" align="top" />';
				$fileDiffIcon = '<a href="#" onclick="diff(\'' . urlencode($filePath) . '\', ' . $this->workingCopy->getUid() . '); return false">' . $fileDiffIcon . '</a>';
			} else {
				$fileDiffIcon = '&nbsp;';
			}

			$fileMarkerArray = array();
			$checkboxId = 'file_' . t3lib_div::shortMD5($filePath);
			$fileMarkerArray['###CHECKBOX###'] = '<input type="checkbox" name="modVars[include][]" id="' . $checkboxId . '" value="' . urlencode($filePath) . '" onclick="return false" />';
			$fileMarkerArray['###CHECKBOX_ID###'] = $checkboxId;
			$fileMarkerArray['###FILE_ICON###'] = $fileIcon;
			$fileMarkerArray['###FILE_PATH###'] = $filePath ? $filePath : '&nbsp;';
			$fileMarkerArray['###FILE_PATH_RELATIVE###'] = $filePathRelative ? $filePathRelative : '&nbsp;';
			$fileMarkerArray['###FILE_EXTENSION###'] = $fileExtension ? $fileExtension : ' - ';
			$fileMarkerArray['###FILE_TEXT_STATUS###'] = $fileTextStatus ? $fileTextStatus : '&nbsp;';
			$fileMarkerArray['###FILE_PROPERTY_STATUS###'] = $filePropertyStatus ? $filePropertyStatus : '&nbsp;';
			$fileMarkerArray['###FILE_LOCK###'] = $fileLock ? $fileLock : '&nbsp;';
			$fileMarkerArray['###FILE_DIFF_ICON###'] = $fileDiffIcon;

			$fileList .= tslib_cObj::substituteMarkerArray($fileTemplate, $fileMarkerArray);
		}
		if ($fileList === '') {
			$infoText = $GLOBALS['LANG']->getLL('no_files_changed');
			if ($hasNonVersionedFiles) {
				$infoText.= '<br />' . $GLOBALS['LANG']->getLL('note') . ': ' . $GLOBALS['LANG']->getLL('contains_unversioned_items');
			}
			return '<div class="infobox">' . $infoText . '</div>';
		}
		$content = tslib_cObj::substituteSubpart($content, 'SUBPART_FILE', $fileList);

		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * Show log of the current working copy
	 *
	 * @return string summary/authentication view
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function log() {
		if (!t3lib_div::GPvar('ok')) {
			return $this->logPreview();
		}
		$markerArray = array();
		$args = array($this->workingCopy->getCurrentPath());
		$revisionRange = (integer)t3lib_div::GPvar('revision_from') . ':' . (integer)t3lib_div::GPvar('revision_to');
		$switches = array('xml' => TRUE, 'revision' => $revisionRange);
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('log', $args, $switches);

		if ($this->svn->authenticationFailed()) {
			return $this->logPreview($GLOBALS['LANG']->getLL('authentication_failed'));
		}

		if ($this->modVars['auth_mode'] === 'explicit' && !empty($this->modVars['save_auth'])) {
			$this->saveUsernameAndPasswordToCookie($this->modVars['username'], $this->modVars['password']);
		}
		if (count($this->svn->getOutput()) === 0) {
			return $this->logPreview('<a href="#" onclick="top.goToModule(\'tools_em\', 0, \'CMD[showExt]=np_subversion&SET[singleDetails]=info\');this.blur();return false;">' . $GLOBALS['LANG']->getLL('no_output') . '</a>');
		}

		$logXml = simplexml_load_string($this->svn->getOutputString(), 'SimpleXMLElement', LIBXML_NOERROR);
		if ($logXml === FALSE) {
			return $this->logPreview('Could not parse XML');
		}

		$content = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_POST_LOG###');
		$logRowTemplate = tslib_cObj::getSubpart($content, '###SUBPART_ROW###');

		$rows = '';
		if (isset($logXml->logentry) && count($logXml->logentry) > 0) {
			foreach($logXml->logentry as $logEntry) {
				$rowMarkerArray['###REVISION###'] = (integer)$logEntry->attributes()->revision;
				$rowMarkerArray['###AUTHOR###'] = htmlspecialchars((string)$logEntry->author);
				$date = new DateTime((string)$logEntry->date);
				$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$rowMarkerArray['###DATE###'] = $date->format('d.m.Y H:i:s');
				$rowMarkerArray['###MESSAGE###'] = nl2br(htmlspecialchars((string)$logEntry->msg));
				$rows .= tslib_cObj::substituteMarkerArray($logRowTemplate, $rowMarkerArray);
			}
		}
		$content = tslib_cObj::substituteSubpart($content, 'SUBPART_ROW', $rows);

		$markerArray['###ROWS###'] = $rows;
		return tslib_cObj::substituteMarkerArray($content, $markerArray);
	}

	/**
	 * This method is called from log() to allow interaction and to display error messages
	 *
	 * @param string $errorMessage if not null, an error occured (e.g. authentication failed) and marker ###ERROR### will be substituted with $errorMessage
	 * @return string log "preview" / error message
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function logPreview($errorMessage = NULL) {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_PRE_LOG###');
		$markerArray = array();
		$markerArray['###AUTHENTICATION###'] = $this->getAuthenticationSubpart();

		if (!$this->svn->isWorkingCopy($this->workingCopy->getAbsolutePath())) {
			return '<div class="errorbox">' . sprintf($GLOBALS['LANG']->getLL('no_working_copy'), $this->truncatePath($this->workingCopy->getAbsolutePath())) . '</div>';
		}

		if ($errorMessage != NULL) {
			$markerArray['###ERROR###'] = '<div class="errorbox">' . $errorMessage . '</div>';
		} else {
			$markerArray['###ERROR###'] = '';
		}

		$revisionInfos = (array)$this->getRevisions($this->workingCopy->getAbsolutePath());
		$fromRevision = (integer)array_pop(array_keys($revisionInfos));
		$markerArray['###REVISION_SELECTOR_FROM###'] = $this->revisionSelector($revisionInfos, $fromRevision, FALSE, 'revision_from');
		$markerArray['###REVISION_SELECTOR_TO###'] = $this->revisionSelector($revisionInfos, 'HEAD', FALSE, 'revision_to');

		$markerArray['###HIDDENFIELDS###'] = '
			<input type="hidden" name="wc" value="' . urlencode($this->workingCopy->getUid()) . '" />
			<input type="hidden" name="cmd" value="log" />';

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * substitutes markers in the header subpart of the template (local path, working copy infos,...)
	 *
	 * @return string substituted header subpart
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getHeaderSubpart() {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_HEADER###');
		$markerArray = array();
		$markerArray['###LOCAL_PATH_LABEL###'] = $GLOBALS['LANG']->getLL('local_path') . ':';
		$localPath = $this->workingCopy->getCurrentPath();
		$relativeLocalPath = $this->truncatePath($localPath);
		$markerArray['###LOCAL_PATH###'] = htmlspecialchars($relativeLocalPath);
		$markerArray['###LOCAL_PATH_CROPPED###'] = htmlspecialchars(tx_npsubversion_div::cropFromCenter($relativeLocalPath, 80));
		$markerArray['###REPOSITORY_LABEL###'] = $GLOBALS['LANG']->getLL('repository') . ':';
		$markerArray['###REPOSITORY###'] = htmlspecialchars($this->workingCopy->getUrl());
		$markerArray['###REPOSITORY_CROPPED###'] = htmlspecialchars(tx_npsubversion_div::cropFromCenter($this->workingCopy->getUrl(), 80));
		$markerArray['###ROOT_LABEL###'] = $GLOBALS['LANG']->getLL('root') . ':';
		$markerArray['###ROOT###'] = htmlspecialchars($this->workingCopy->getRepositoryUrl());
		$markerArray['###ROOT_CROPPED###'] = htmlspecialchars(tx_npsubversion_div::cropFromCenter($this->workingCopy->getRepositoryUrl(), 80));

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * substitutes markers in the authentication subpart of the template
	 *
	 * @return string substituted authentication subpart
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getAuthenticationSubpart() {
		$template = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_AUTHENTICATION###');
		$markerArray = array();
		if ($this->modVars['auth_mode'] === 'explicit') {
			$authModeExplicit = TRUE;
			$saveAuth = empty($this->modVars['save_auth']) ? FALSE : TRUE;
			$username = $this->modVars['username'];
			$password = $this->modVars['password'];
		} else {
			$username = $this->getUsernameFromCookie();
			$password = $this->getPasswordFromCookie();
			$saveAuth = strlen($username) > 0;
		}

		$markerArray['###USERNAME###'] = htmlspecialchars($username);
		$markerArray['###PASSWORD###'] = htmlspecialchars($password);

		if ($this->svn->authenticationFailed()) {
			$markerArray['###HEADER###'] = $GLOBALS['LANG']->getLL('authentication_failed_header');
			$authModeExplicit = TRUE;
		} else {
			$markerArray['###HEADER###'] = $GLOBALS['LANG']->getLL('authentication_header');
			$authModeExplicit = ($this->modVars['auth_mode'] != 'implicit' && strlen($username) > 0);
		}

		$markerArray['###USERNAME_LABEL###'] = $GLOBALS['LANG']->getLL('username_label');
		$markerArray['###PASSWORD_LABEL###'] = $GLOBALS['LANG']->getLL('password_label');
		$markerArray['###SAVE_AUTH_LABEL###'] = $GLOBALS['LANG']->getLL('save_auth_label');
		$markerArray['###SAVE_AUTH_NOTICE###'] = $GLOBALS['LANG']->getLL('save_auth_notice');

		$defaultUsername = $this->workingCopy->getUsername();
		if (!empty($defaultUsername)) {
			$markerArray['###AUTHENTICATE_AS###'] = sprintf(
				$GLOBALS['LANG']->getLL('authenticate_as'),
				$defaultUsername
			);
		} else {
			$markerArray['###AUTHENTICATE_AS###'] = $GLOBALS['LANG']->getLL('no_authentication');
		}

		$markerArray['###AUTHENTICATION_IMPLICIT###'] = $GLOBALS['LANG']->getLL('authentication_implicit');
		$markerArray['###AUTHENTICATION_EXPLICIT###'] = $GLOBALS['LANG']->getLL('authentication_explicit');

		if ($authModeExplicit) {
			$markerArray['###AUTH_MODE_IMPLICIT_CHECKED###'] = '';
			$markerArray['###AUTH_MODE_EXPLICIT_CHECKED###'] = 'checked="checked"';
		} else {
			$markerArray['###AUTH_MODE_IMPLICIT_CHECKED###'] = 'checked="checked"';
			$markerArray['###AUTH_MODE_EXPLICIT_CHECKED###'] = '';
		}

		$markerArray['###SAVE_AUTH_CHECKED###'] = $saveAuth ? 'checked="checked"' : '';

		return tslib_cObj::substituteMarkerArray($template, $markerArray);
	}

	/**
	 * iterates through the lines returned by a SVN command and substitutes subpart with equivalent but more readable information
	 *
	 * @return string assembled message/file-row subparts (see tx_npsubversion_cm1.html)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getStatusRowsSubpart() {
		$fileRowTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_FILE_ROW###');
		$messageRowTemplate = tslib_cObj::getSubpart($this->templateCode, '###SUBPART_MESSAGE_ROW###');
		$rows = '';
		foreach ($this->svn->getOutput() as $line) {
			$action = substr($line, 0, 4);
			$message = substr($line, 5);
			$rowTemplate = $fileRowTemplate;
			switch ($action) {
					// added
				case 'A   ':
					$cssClass = 'added';
					$action = 'Added';
					$message = $this->truncatePath($message);
					break;

					// deleted
				case 'D   ':
					$cssClass = 'deleted';
					$action = 'Deleted';
					$message = $this->truncatePath($message);
					break;

					// updated
				case 'U   ':
					$cssClass = 'updated';
					$action = 'Updated';
					$message = $this->truncatePath($message);
					break;

					// conflict
				case 'C   ':
					$cssClass = 'conflict';
					$action = 'Conflict';
					$message = $this->truncatePath($message);
					break;

					// merged
				case 'G   ':
					$cssClass = 'merged';
					$action = 'Merged';
					$message = $this->truncatePath($message);
					break;

					// Updated property(?)
				case ' U  ':
					$cssClass = 'updated';
					$action = 'Updated';
					$message = $this->truncatePath($message);
					break;

					// error(?)
				case 'svn:':
					$cssClass = 'error';
					$rowTemplate = $messageRowTemplate;
					break;

				default:
					$cssClass = '';
					$message = $line;
					$rowTemplate = $messageRowTemplate;
			}
			$rowMarkerArray['###CSS_CLASS###'] = $cssClass;
			$rowMarkerArray['###ACTION###'] = $action;
			$rowMarkerArray['###MESSAGE###'] = $message;

			$rows .= tslib_cObj::substituteMarkerArray($rowTemplate, $rowMarkerArray);
		}
		return $rows;
	}

	/**
	 * reads username for the current repository from browser cookie
	 *
	 * @return string username or empty string if cookie did not contain a username linked to the current repository URL
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getUsernameFromCookie() {
		$cookieData = $this->getCookieData();
		if (isset($cookieData[$this->workingCopy->getRepositoryUrl()]['username'])) {
			return $cookieData[$this->workingCopy->getRepositoryUrl()]['username'];
		}
		return '';
	}

	/**
	 * reads password for the current repository from browser cookie
	 *
	 * @return string password or empty string if cookie did not contain a password linked to the current repository URL
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getPasswordFromCookie() {
		$cookieData = $this->getCookieData();
		if (isset($cookieData[$this->workingCopy->getRepositoryUrl()]['password'])) {
			return $cookieData[$this->workingCopy->getRepositoryUrl()]['password'];
		}
		return '';
	}

	/**
	 * Stores username and password in a browser cookie
	 *
	 * @param string $username username to be stored
	 * @param string $password password to be stored
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function saveUsernameAndPasswordToCookie($username, $password) {
		$cookieData = $this->getCookieData();
		$cookieData[$this->workingCopy->getRepositoryUrl()]['username'] = $username;
		$cookieData[$this->workingCopy->getRepositoryUrl()]['password'] = $password;

		$this->setCookieData($cookieData);
	}

	/**
	 * Loads and extracts serialized cookie information
	 *
	 * @return array cookie data
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getCookieData() {
		$cookieLifetime = (integer)$this->conf['cookie_lifetime'];
		if ($cookieLifetime === -1) {
			return array();
		}
		if ($this->cookieData === NULL) {
			$cookieDataEncoded = $_COOKIE['np_subversion'];
			$cookieDataSerialized = base64_decode($cookieDataEncoded);
			$cookieData = unserialize($cookieDataSerialized);
			if (is_array($cookieData)) {
				$this->cookieData = $cookieData;
			} else {
				$this->cookieData = array();
			}
		}
		return $this->cookieData;
	}

	/**
	 * Serializes and stores an array in a browser cookie with configured cookie lifetime
	 *
	 * @param array $data the data to be serialized and stored in the cookie (usually username & password)
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function setCookieData($data) {
		$cookieLifetime = (integer)$this->conf['cookie_lifetime'];
		if ($cookieLifetime === -1) {
			return;
		}
		if ($cookieLifetime > 0) {
			$expiry = time()+$cookieLifetime;
		} else {
			$expiry = 0; // session
		}
		$dataSerialized = serialize($data);
		$dataEncoded = base64_encode($dataSerialized);
		setcookie('np_subversion', $dataEncoded, $expiry);
	}

	/**
	 * Loads a list of previously entered log messages from the currents BE users user cache
	 *
	 * @return array log message history
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function loadLogMessageHistory() {
		$logMessageHistory = $GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'];
		if (!is_array($logMessageHistory)) {
			return array();
		}
		return $logMessageHistory;
	}

	/**
	 * Creates a dropdown from the log message history
	 *
	 * @return string HTML code of the dropdown
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function logMessageSelector() {
		$logMessages = $this->loadLogMessageHistory();
		$content = '<select onchange="document.getElementById(\'message\').value = this.value">
			<option class="header" value="">&lt;log message history&gt;</option>';
		if (count($logMessages) > 0) {
			foreach ($logMessages as $logMessage) {
				$content .= '<option value="' . htmlspecialchars($logMessage) . '">' . htmlspecialchars(stripslashes($logMessage)) . '</option>';
			}
		}
		$content .= '</select>';
		return $content;
	}

	/**
	 * Adds a message to the log message history, if not already registered.
	 * Strips off older messages if history exeeds 10 entries and saves history in user cache of the current BE user
	 *
	 * @param string $message message to be appended to log message history
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function saveLogMessageToHistory($message) {
		if (strlen($message) === 0) {
			return;
		}
		if (is_array($GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'])) {
			if (in_array($message, $GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'])) {
				return;
			}
			array_unshift($GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'], $message);

			if (count($GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory']) > 10) {
				array_splice($GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'], 10);
			}
		} else {
			$GLOBALS['BE_USER']->uc['moduleData']['tools_txnpsubversionM1'][$this->workingCopy->getRepositoryUrl()]['loghistory'][] = $message;
		}
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
	}

	/**
	 * interates through the list of paths and calls t3lib_div::fixPermissions() on files and setPermissionsForDirectory on folders to change filesystem permissions
	 * disabled if UMASK is set
	 *
	 * @param array $paths list of absolute paths
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function setPermissions($paths) {
			// if UMASK is set, we don't need to fix permissions afterwards
		if (strlen($this->conf['umask']) > 0) {
			return;
		}
		foreach($paths as $path) {
			if (is_dir($path)) {
				$this->setPermissionsForDirectory($path);
			} else {
				t3lib_div::fixPermissions($path);
			}
		}
	}

	/**
	 * sets create-mask and -group of specified directory according to the defaults of this TYPO3 installation
	 *
	 *
	 * @param string $path absolute directory path
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function setPermissionsForDirectory($path) {
		chmod($path, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']));
		if($GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup']) {
			chgrp($path, $GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup']);
		}
	}

	/**
	 * Adds a junk of javascript to the page header causing the navigation frame to reload
	 *
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function requestNavFrameReload() {
		t3lib_BEfunc::setUpdateSignal('updateFolderTree');
	}

	/**
	 * returns the last part of specified path relative to PATH_site (TYPO3 root)
	 *
	 * @param string $path absolute path to be truncated
	 * @return string the path, relative to site root
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function truncatePath($path) {
		$path = str_replace('\\', '/', trim($path));
		$truncLength = strlen(PATH_site);

		return substr($path, $truncLength);
	}

	/**
	 * returns backup path as specified in the extension configuration
	 *
	 * @return string absolute path to backup root
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getBackupRoot() {
		return PATH_site . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['backup_path'];
	}

	/**
	 * creates backup path in the format [backup root]/[working copy path]_[timestamp]
	 *
	 * @return string absolute path to unique backup directory
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getBackupPath() {
		if (!$this->backupPath) {
			$path = $this->workingCopy->isFolder() ? $this->workingCopy->getPath() : $this->workingCopy->getExtensionKey();
			$this->backupPath = tx_npsubversion_div::addTrailingSlash($this->getBackupRoot()) . $path . '_' . date('YmdHis');
		}
		return $this->backupPath;
	}

	/**
	 * Returns backup path relative to PATH_site (TYPO3 root)
	 * @see getBackupPath()
	 *
	 * @return string relative backup path
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getBackupPathRelative() {
		return $this->truncatePath($this->getBackupPath());
	}

	/**
	 * Retrieves all base revisions of a file and their respective author
	 * by calling "svn log"
	 *
	 * @param string $path absolute filepath to get revisions for
	 * @return array an array with revisions as keys and author as values
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function getRevisions($path) {
		$revisionInfos = array();
		if (!strlen(trim($path))) {
			return $revisionInfos;
		}
		if (!extension_loaded('SimpleXML')) {
			$revisionInfos[] = 'SimpleXML not loaded!';
			return $revisionInfos;
		}
		$args = array($path);
		$switches = array('stop-on-copy' => TRUE, 'quiet' => TRUE, 'xml' => TRUE, 'limit' => 10);
		$this->addAuthenticationSwitches($switches);

		$this->svn->exec('log', $args, $switches);

		if ($this->svn->authenticationFailed()) {
			$revisionInfos[] = 'Authentication failed!';
			return $revisionInfos;
		}

		$log = simplexml_load_string($this->svn->getOutputString());
		if (is_array($log->logentry) || $log->logentry instanceof Traversable) {
			foreach($log->logentry as $logEntry) {
				$attributes = $logEntry->attributes();
				$revision = $attributes->revision;
				$author = $logEntry->author;
				$revisionInfos[(int)$revision] = (string)$author;
			}
		}
		return $revisionInfos;
	}

	/**
	 * Creates a dropdown to select a revision for a given file.
	 *
	 * @param array $revisionInfos an array with revisions as keys and author names as values
	 * @param integer $currentRevision the current revision to be selected
	 * @param boolean $submitOnChange if TRUE, the form will be submitted as soon as the revision is changed
	 * @param string $fieldName name of the selector
	 * @return string the revision selector html code
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function revisionSelector($revisionInfos, $currentRevision, $submitOnChange = FALSE, $fieldName = 'revision') {
		$onChange = '';
		if ($submitOnChange) {
			$onChange = 'onchange="form.submit()"';
		}
		$code = '<select id="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($fieldName) . '"' . $onChange . '>' . chr(10);

		$firstEntry = TRUE;
		foreach($revisionInfos as $revision => $author) {
			$selected = '';
			if ($currentRevision == $revision) {
				$selected = 'selected="selected"';
			}
			$label = htmlspecialchars($revision) . ' (' . htmlspecialchars($author) . ')';
			if ($firstEntry) {
				$label .= ' - HEAD';
				$firstEntry = FALSE;
			}
			$code .= '<option value="' . htmlspecialchars($revision) . '"' . $selected . '>' . $label . '</option>' . chr(10);
		}
		$code .= '</select>';

		return $code;
	}

	/**
	 * adds authentication switches (non-interactive, no-auth-cache, username & password)
	 *
	 * @param array $switches switches to be modified
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function addAuthenticationSwitches(&$switches) {
		$switches['non-interactive'] = TRUE;
		$switches['no-auth-cache'] = TRUE;

			// if username/passwords are entered use them for authentication
		if ($this->modVars['auth_mode'] === 'explicit') {
			$username = $this->modVars['username'];
			$password = $this->modVars['password'];

			// default authentication
		} elseif ($this->modVars['auth_mode'] === 'implicit') {
				// if existent use credentials from repository record
			if ($this->workingCopy->hasCredentials()) {
				$username = $this->workingCopy->getUsername();
				$password = $this->workingCopy->getPassword();
			}

			// otherwise try to get credentials from cookie
		} else {
			$username = $this->getUsernameFromCookie();
			$password = $this->getPasswordFromCookie();
		}

		if (!empty($username)) {
			$switches['username'] = $username;
			$switches['password'] = $password;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/cm1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/cm1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_nsubversion_cm1');
$SOBE->init();

$SOBE->main();
$SOBE->printContent();
?>