<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TCA']['tx_npsubversion_repository'] = array (
	'ctrl' => $GLOBALS['TCA']['tx_npsubversion_repository']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'rep_title, rep_url, rep_username, rep_password'
	),
	'feInterface' => $GLOBALS['TCA']['tx_npsubversion_repository']['feInterface'],
	'columns' => array (
		'rep_title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_repository.rep_title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'rep_url' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_repository.rep_url',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'rep_username' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_repository.rep_username',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'rep_password' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_repository.rep_password',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'password',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'rep_title;;;;2-2-2, rep_url, rep_username, rep_password')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$GLOBALS['TCA']['tx_npsubversion_workingcopy'] = array (
	'ctrl' => $GLOBALS['TCA']['tx_npsubversion_workingcopy']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'wc_title, repository, wc_url, wc_type, wc_target_type, wc_extension, wc_path'
	),
	'feInterface' => $GLOBALS['TCA']['tx_npsubversion_workingcopy']['feInterface'],
	'columns' => array (
		'wc_title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'repository' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.repository',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_npsubversion_repository',
				'foreign_table_where' => 'ORDER BY tx_npsubversion_repository.uid',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new repository',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_npsubversion_repository',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
				),
			)
		),
		'wc_url' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_url',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'wc_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_type',
			'config' => Array (
				'type' => 'radio',
				'items' => Array (
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_type.I.0', '0'),
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_type.I.1', '1'),
				),
			)
		),
		'wc_target_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_target_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_target_type.I.0', 0),
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_target_type.I.1', 1),
				),
			)
		),
		'wc_extension' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_extension',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'wc_extension_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_extension_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_extension_type.I.0', 0),
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_extension_type.I.1', 1),
					Array('LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_extension_type.I.2', 2),
				),
			)
		),
		'wc_path' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_path',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'wc_no_backup' => Array (
			'displayCond' => 'FIELD:wc_type:=:1',
			'exclude' => 1,
			'label' => 'LLL:EXT:np_subversion/locallang_db.xml:tx_npsubversion_workingcopy.wc_no_backup',
			'config' => Array (
				'type' => 'check',
			)
		),
	),
	'types' => array (
		'0' => array(
			'showitem' => 'wc_title;;;;2-2-2, repository, wc_url, wc_type, wc_target_type, wc_path, wc_no_backup'
		),
		'1' => array(
			'showitem' => 'wc_title;;;;2-2-2, repository, wc_url, wc_type, wc_target_type, wc_extension, wc_extension_type, wc_no_backup'
		)
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>