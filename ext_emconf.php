<?php

########################################################################
# Extension Manager/Repository config file for ext: "np_subversion"
#
# Auto generated 10-11-2010 11:59
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Subversion integration',
	'description' => 'seamlessly integrates Subversion client features into TYPO3',
	'category' => 'module',
	'author' => 'Bastian Waidelich',
	'author_email' => 'waidelich@network-publishing.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'cm1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'network.publishing, Cologne',
	'version' => '0.9.1',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.0.0-5.3.99',
			'typo3' => '3.8.0-4.4.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:54:{s:29:"class.tx_npsubversion_cm1.php";s:4:"e15f";s:36:"class.tx_npsubversion_diffparser.php";s:4:"22b4";s:29:"class.tx_npsubversion_div.php";s:4:"acfe";s:36:"class.tx_npsubversion_filestatus.php";s:4:"24d5";s:31:"class.tx_npsubversion_model.php";s:4:"7545";s:29:"class.tx_npsubversion_svn.php";s:4:"2938";s:37:"class.tx_npsubversion_workingcopy.php";s:4:"7197";s:21:"ext_conf_template.txt";s:4:"203f";s:12:"ext_icon.gif";s:4:"5f3e";s:17:"ext_localconf.php";s:4:"3b72";s:14:"ext_tables.php";s:4:"ce85";s:14:"ext_tables.sql";s:4:"15ac";s:35:"icon_tx_npsubversion_repository.gif";s:4:"bc85";s:36:"icon_tx_npsubversion_workingcopy.gif";s:4:"c93a";s:13:"locallang.xml";s:4:"4175";s:16:"locallang_db.xml";s:4:"1c92";s:7:"tca.php";s:4:"d240";s:24:"res/module_file_list.gif";s:4:"d436";s:32:"res/np_subversion_mod_header.gif";s:4:"2767";s:28:"res/np_subversion_turtle.gif";s:4:"8f91";s:16:"res/prototype.js";s:4:"85ef";s:19:"res/icons/blame.gif";s:4:"5497";s:22:"res/icons/checkout.gif";s:4:"9ef6";s:21:"res/icons/cleanup.gif";s:4:"66a9";s:20:"res/icons/commit.gif";s:4:"52a2";s:20:"res/icons/delete.gif";s:4:"81a0";s:18:"res/icons/diff.gif";s:4:"4aac";s:20:"res/icons/export.gif";s:4:"be56";s:27:"res/icons/overlay_added.gif";s:4:"73d8";s:30:"res/icons/overlay_conflict.gif";s:4:"3572";s:29:"res/icons/overlay_deleted.gif";s:4:"1ed6";s:28:"res/icons/overlay_locked.gif";s:4:"cf1f";s:30:"res/icons/overlay_modified.gif";s:4:"11cc";s:30:"res/icons/overlay_readonly.gif";s:4:"0ad5";s:33:"res/icons/overlay_workingcopy.gif";s:4:"e138";s:20:"res/icons/revert.gif";s:4:"ecc6";s:22:"res/icons/tortoise.gif";s:4:"f5cd";s:20:"res/icons/update.gif";s:4:"8318";s:14:"doc/manual.sxw";s:4:"a360";s:55:"interfaces/interface.tx_npsubversion_svncommandhook.php";s:4:"95ec";s:38:"tests/tx_npsubversion_div_testcase.php";s:4:"c366";s:28:"xclass/class.ux_fileList.php";s:4:"fa14";s:38:"xclass/class.ux_filelistfoldertree.php";s:4:"b76b";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"9ef6";s:24:"cm1/cm_icon_activate.gif";s:4:"9ef6";s:12:"cm1/conf.php";s:4:"0ff6";s:11:"cm1/diff.js";s:4:"61b5";s:13:"cm1/index.php";s:4:"dabf";s:17:"cm1/locallang.xml";s:4:"0ddc";s:14:"cm1/scripts.js";s:4:"f7c0";s:14:"cm1/styles.css";s:4:"ea4d";s:28:"cm1/tx_npsubversion_cm1.html";s:4:"30ae";s:45:"hooks/class.tx_npsubversion_hooks_logExec.php";s:4:"eff2";}',
	'suggests' => array(
	),
);

?>