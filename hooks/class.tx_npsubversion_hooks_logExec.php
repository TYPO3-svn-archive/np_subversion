<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 Bastian Waidelich <waidelich@network-publishing.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 * $Id$
 *
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 * @package TYPO3
 * @subpackage np_subversion
 */

require_once(t3lib_extMgm::extPath('np_subversion') . 'interfaces/interface.tx_npsubversion_svncommandhook.php');

class tx_npsubversion_hooks_logExec implements tx_npsubversion_svnCommandHook {

	protected $svnWrapper = NULL;

	/**
	 * initializes the hook object
	 *
	 * @param tx_npsubversion_svn parent tx_npsubversion_svn object
	 * @return void
	 */
	public function init(tx_npsubversion_svn $parentObject) {
		$this->svnWrapper = $parentObject;
	}

	/**
	 * Pre-process svn command
	 *
	 * @param string $svnCommand Subversion command ("update", "commit", "export", ...)
	 * @param array $arguments Subversion arguments (pathes, URLs)
	 * @param array $switches Subversion switches ("--encoding", "--file", ...)
	 * @param boolean $cancelled Specifies, whether this command has been aborted. Set this to TRUE to avoid execution of the command.
	 * @param string $abortMessage
	 * @return void
	 */
	public function exec_preProcess(&$svnCommand, array &$arguments, array &$switches, &$cancelled, &$abortMessage) {
	}

	/**
	 * Post-process svn command
	 *
	 * @param string $svnCommand Subversion command ("update", "commit", "export", ...)
	 * @param array $arguments Subversion arguments (pathes, URLs)
	 * @param array $switches Subversion switches ("--encoding", "--file", ...)
	 * @return void
	 */
	public function exec_postProcess(&$svnCommand, array $arguments, array $switches) {
		if (!TYPO3_DLOG) {
			return;
		}
		$commandsToBeLogged = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['np_subversion']['log_svn_commands']);
		if (!in_array($svnCommand, $commandsToBeLogged)) {
			return;
		}
		$severity = $this->svnWrapper->getStatus() === 0 ? 0 : 2;
		$data = array(
			'svnCommand' => $svnCommand,
			'arguments' => $arguments,
			'switches' => $switches,
			'output' => $this->svnWrapper->getOutputString()
		);
		t3lib_div::devLog('command "' . $svnCommand . '"', 'np_subversion', $severity, $data);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/hooks/class.tx_npsubversion_hooks_logExec.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/hooks/class.tx_npsubversion_hooks_logExec.php']);
}
?>