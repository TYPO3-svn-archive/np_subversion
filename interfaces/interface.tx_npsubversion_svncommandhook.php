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
 * Interface for classes which hook into tx_npsubversion_svn
 *
 * $Id$
 *
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 * @package TYPO3
 * @subpackage np_subversion
 */
interface tx_npsubversion_svnCommandHook {

	/**
	 * initializes the hook object
	 *
	 * @param tx_npsubversion_svn parent tx_npsubversion_svn object
	 * @return void
	 */
	public function init(tx_npsubversion_svn $parentObject);

	/**
	 * Pre-process svn command
	 *
	 * @param string $svnCommand Subversion command ("update", "commit", "export", ...)
	 * @param array $arguments Subversion arguments (pathes, URLs)
	 * @param array $switches Subversion switches ("--encoding", "--file", ...)
	 * @param boolean $cancelled Specifies, whether this command has been aborted. Set this to TRUE to avoid execution of the command.
	 * @param string $abortMessage set this to your custom message. Only effective if $cancelled is TRUE
	 * @return void
	 */
	public function exec_preProcess(&$svnCommand, array &$arguments, array &$switches, &$cancelled, &$abortMessage);

	/**
	 * Post-process svn command
	 *
	 * @param string $svnCommand Subversion command ("update", "commit", "export", ...)
	 * @param array $arguments Subversion arguments (pathes, URLs)
	 * @param array $switches Subversion switches ("--encoding", "--file", ...)
	 * @return void
	 */
	public function exec_postProcess(&$svnCommand, array $arguments, array $switches);
}
?>