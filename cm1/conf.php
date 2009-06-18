<?php
// Check if MOD is installed and executed in local context, if true modify paths
if (substr_count($_SERVER['SCRIPT_FILENAME'], 'typo3conf') == 0) {
	define('TYPO3_MOD_PATH', 'ext/np_subversion/cm1/');
} else {
	define('TYPO3_MOD_PATH', '../typo3conf/ext/np_subversion/cm1/');
}

$BACK_PATH = '../../../../typo3/';
$MCONF['name'] = 'xMOD_tx_npsubversion_cm1';
?>