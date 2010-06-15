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

require_once(PATH_typo3 . 'mod/tools/em/class.em_index.php');

/**
 * Utility-Class for reoccurring tasks within np_subversion
 *
 * 
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_div {

	/**
	 * Adds a trailing slash to the given path (if its not already there)
	 *
	 * @param string $path
	 * @return string path with trailing slash
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function addTrailingSlash($path) {
		$path = str_replace('\\','/', $path);
		if (substr($path, -1) != '/') {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * Removes trailing slash from the given path (if existent)
	 *
	 * @param string $path
	 * @return string path without trailing slash
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function stripTrailingSlash($path) {
		$path = str_replace('\\','/', $path);
		if (substr($path, -1) === '/') {
			$path = substr($path, 0, -1);
		}
		return $path;
	}

	/**
	 * combines two paths by adding one (and only one) slash between them
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return string combined path
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function combinePaths($path1, $path2) {
		$path1 = self::addTrailingSlash($path1);
		return $path1 . ltrim($path2, '/ ');
	}

	/**
	 * iterates through a directory and deletes all files recursively
	 *
	 * @param string $path absolute directory path
	 * @return boolean TRUE on success, otherwise FALSE
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function rmdir_recursive($path) {
		$directoryHandle = opendir($path);
		if ($directoryHandle === FALSE) {
			return FALSE;
		}
		$old_cwd = getcwd();
		chdir($path);

		while (($file = readdir($directoryHandle))){
			if ($file === '.' || $file === '..') {
				continue;
			}
			if (is_dir($file)){
				if (!self::rmdir_recursive($file)) {
					return FALSE;
				}
			}else{
				if (is_writable($file) === FALSE) {
					chmod($file, 0600);
				}
				if (!unlink($file)) {
					return FALSE;
				}
			}
		}

		closedir($directoryHandle);
		chdir($old_cwd);

		return rmdir($path);
	}

	/**
	 * Crops a string from its center (e.g. to obtain relevant information about an URL)
	 *
	 * @param string $url can be any string, but it's especially usefull for URLs
	 * @param integer $maxChars maximum length of resulting string
	 * @return string cropped string (if exceeds $maxChars)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function cropFromCenter($url, $maxCharacters) {
		$overflow = strlen($url) - $maxCharacters;
		if ($overflow < 4) {
			return $url;
		}
		$cropStart = floor((strlen($url) / 2) - ($overflow / 2));
		$cropEnd = $cropStart + $overflow + 3;

		$firstPart = substr($url, 0, $cropStart);
		$secondPart = substr($url, $cropEnd);

		return $firstPart . '...' . $secondPart;
	}

	/**
	 * Gets the version of an extension from extension manager
	 *
	 * @param string $extensionKey extension key
	 * @return string extension version in the format "x.x.x"
	 * @author Axel Boeswetter <boeswetter@portrino.de>
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function getExtensionVersion($extensionKey) {
			// instantiate extension manager index class
		$extensionManager = t3lib_div::makeInstance('SC_mod_tools_em_index');
		$extensionManager->init();
			// get extension information
		list($extensionList) = $extensionManager->getInstalledExtensions();
		return $extensionList[$extensionKey]['EM_CONF']['version'];
	}

	/**
	 * Increases extension version and updates change hash in ext_emconf.php file
	 *
	 * @param string $extensionKey extension key
	 * @param string $increaseVersionPart version part to increase by 1. One of "main", "sub" or "dev"
	 * @return void
	 * @author Axel Boeswetter <boeswetter@portrino.de>
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public static function increaseExtensionVersion($extensionKey, $increaseVersionPart) {
			// instantiate extension manager index class
		$extensionManager = t3lib_div::makeInstance('SC_mod_tools_em_index');
		$extensionManager->init();
			// get extension information
		list($extensionList) = $extensionManager->getInstalledExtensions();
		if (!isset($extensionList[$extensionKey]['EM_CONF']['version'])) {
			throw new RuntimeException('can\'t retrieve extension info for extension "' . $extensionKey . '"');
		}
		$extensionInformation = $extensionList[$extensionKey];
		$newVersion = current($extensionManager->renderVersion($extensionInformation['EM_CONF']['version'], $increaseVersionPart));
		$extensionInformation['EM_CONF']['version'] = $newVersion;
		$extensionManager->updateLocalEM_CONF($extensionKey, $extensionInformation);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_div.php']);
}
?>