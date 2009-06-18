<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE == 'BE') {
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_npsubversion_cm1',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_npsubversion_cm1.php'
	);
}

$TCA['tx_npsubversion_repository'] = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_repository',
		'label' => 'rep_title',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'tstamp'	=> 'tstamp',
		'crdate'	=> 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY rep_title',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_npsubversion_repository.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'rep_title, rep_url, rep_username, rep_password',
	)
);

$TCA['tx_npsubversion_workingcopy'] = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy',
		'label' => 'wc_title',
		'label_alt' => 'repository',
		'label_alt_force' => 1,
		'adminOnly' => 1,
		'rootLevel' => 1,
		'tstamp'	=> 'tstamp',
		'crdate'	=> 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY wc_title',
		'delete' => 'deleted',
		'type' => 'wc_target_type',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_npsubversion_workingcopy.gif',
		'requestUpdate'		=> 'wc_type',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'wc_title, repository, wc_url, wc_type, wc_target_type, wc_extension, wc_path, wc_no_backup',
	)
);

$TBE_STYLES['skinImg']['MOD:file_list/list.gif'] = array(t3lib_extMgm::extRelPath($_EXTKEY).'res/module_file_list.gif','width="22" height="24"');
?>