<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['svn_path'] = $_EXTCONF['svn_path'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['svn_config_dir'] = $_EXTCONF['svn_config_dir'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['svn_encoding'] = $_EXTCONF['svn_encoding'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['umask'] = $_EXTCONF['umask'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['backup_path'] = $_EXTCONF['backup_path'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cookie_lifetime'] = $_EXTCONF['cookie_lifetime'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['show_svn_dirs'] = $_EXTCONF['show_svn_dirs'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['log_svn_commands'] = $_EXTCONF['log_svn_commands'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['use_passthru'] = $_EXTCONF['use_passthru'];

$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['typo3/class.filelistfoldertree.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'xclass/class.ux_filelistfoldertree.php';
$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY) . 'xclass/class.ux_fileList.php';

	// enable logging of svn commands
if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['log_svn_commands']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['log_svn_commands'] !== '') {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['svnCommandHook'][] = t3lib_extMgm::extPath($_EXTKEY) . 'hooks/class.tx_npsubversion_hooks_logExec.php:tx_npsubversion_hooks_logExec';
}

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_npsubversion_workingcopy = 1
');
?>