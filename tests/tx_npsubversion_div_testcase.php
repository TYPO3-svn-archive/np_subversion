<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Andy Grunwald (andreas.grunwald@wmdb.de)
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

/**
 * Test case for checking the class "tx_npsubversion_div" (np_subversion/class.tx_npsubversion_div.php)
 *
 * @author	Andy Grunwald <andreas.grunwald@wmdb.de>
 */

require_once (t3lib_extMgm::extPath('np_subversion').'class.tx_npsubversion_div.php');

class tx_npsubversion_div_testcase extends tx_phpunit_testcase {

	/**
	 * @test
	 * @see tx_npsubversion_div::addTrailingSlash
	 */
	public function addTrailingSlashWithoutTrailingSlashOnUnixPath(){
		$path = '/var/www/test/directory';
		
		$this->assertEquals($path.'/', tx_npsubversion_div::addTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::addTrailingSlash
	 */
	public function addTrailingSlashWithTrailingSlashOnUnixPath(){
		$path = '/var/www/test/directory/';
		
		$this->assertEquals($path, tx_npsubversion_div::addTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::addTrailingSlash
	 */
	public function addTrailingSlashWithoutTrailingSlashOnWindowsPath(){
		$path = 'c:\\svn\\text\\directory';
		
		$this->assertEquals('c:/svn/text/directory/', tx_npsubversion_div::addTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::addTrailingSlash
	 */
	public function addTrailingSlashWithTrailingSlashOnWindowsPath(){
		$path = 'c:\\svn\\text\\directory/';
		
		$this->assertEquals('c:/svn/text/directory/', tx_npsubversion_div::addTrailingSlash($path));
	}

	/**
	 * @test
	 * @see tx_npsubversion_div::stripTrailingSlash
	 */
	public function stripTrailingSlashWithoutTrailingSlashOnUnixPath(){
		$path = '/var/www/test/directory';
		
		$this->assertEquals($path, tx_npsubversion_div::stripTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::stripTrailingSlash
	 */
	public function stripTrailingSlashWithTrailingSlashOnUnixPath(){
		$path = '/var/www/test/directory/';
		
		$this->assertEquals(substr($path, 0, -1), tx_npsubversion_div::stripTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::stripTrailingSlash
	 */
	public function stripTrailingSlashWithoutTrailingSlashOnWindowsPath(){
		$path = 'c:\\svn\\text\\directory';
		
		$this->assertEquals('c:/svn/text/directory', tx_npsubversion_div::stripTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::stripTrailingSlash
	 */
	public function stripTrailingSlashWithTrailingSlashOnWindowsPath(){
		$path = 'c:\\svn\\text\\directory';
		
		$this->assertEquals('c:/svn/text/directory', tx_npsubversion_div::stripTrailingSlash($path));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::combinePaths
	 */
	public function combinePathsWithTwoCorrectPaths(){
		$path1 = '/var/www/';
		$path2 = 'example.com/www/typo3conf/ext/np_subversion';
		$this->assertEquals($path1.$path2, tx_npsubversion_div::combinePaths($path1, $path2));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::combinePaths
	 */
	public function combinePathsWithFirstPathEmpty(){
		$path1 = '';
		$path2 = 'example.com/www/typo3conf/ext/np_subversion';
		$this->assertEquals('/example.com/www/typo3conf/ext/np_subversion', tx_npsubversion_div::combinePaths($path1, $path2));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::combinePaths
	 */
	public function combinePathsWithSecondPathEmpty(){
		$path1 = '/var/www/';
		$path2 = '';
		$this->assertEquals('/var/www/', tx_npsubversion_div::combinePaths($path1, $path2));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::combinePaths
	 */
	public function combinePathsWithTwoEmptyPaths(){
		$path1 = '';
		$path2 = '';
		$this->assertEquals('/', tx_npsubversion_div::combinePaths($path1, $path2));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::rmdir_recursive
	 */
	public function rmdir_recursiveWithExistingDir(){
		$pathToTempFile = PATH_site.'typo3temp/np_subversion/unittests/example.tmp';
		t3lib_div::writeFileToTypo3tempDir($pathToTempFile, 'only temp content');
		
		$this->assertTrue(tx_npsubversion_div::rmdir_recursive(PATH_site.'typo3temp/np_subversion/'));
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::cropFromCenter
	 */
	function cropFromCenterWithoutOverflow(){
		$url = 'http://typo3.org/';
		$maxCharacters = 14;
		
		$this->assertEquals($url, tx_npsubversion_div::cropFromCenter($url, $maxCharacters));
		
	}
	
	/**
	 * @test
	 * @see tx_npsubversion_div::cropFromCenter
	 */
	function cropFromCenterWitOverflow(){
		$url = 'http://typo3.org/extensions/repository/';
		$maxCharacters = 20;
		
		$this->assertEquals('http://typ...sitory/', tx_npsubversion_div::cropFromCenter($url, $maxCharacters));
		
	}
}
?>
