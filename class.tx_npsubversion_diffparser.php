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

/**
 * Class for parsing unified diff files
 *
 * @package TYPO3
 * @subpackage tx_npsubversion
 * @author Bastian Waidelich <waidelich@network-publishing.de>
 */
class tx_npsubversion_diffparser {

	/**
	 * Regular expression used to find chunks in unified diff files
	 *
	 * @var string
	 */
	const PATTERN_CHUNKS = '/@@\s\-(?P<start1>\d+),?(?P<lines1>\d*)\s\+(?P<start2>\d+),?(?P<lines2>\d*)\s@@(?P<content>[\s\S]*?)(?=(^@@|[\s\S}]\z))/ms';

	/**
	 * the unified diff returned by "svn diff"
	 *
	 * @var string
	 */
	protected $patch;

	/**
	 * contents of the working copy file
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * an array containing the lines of the working copy file (one line per entry)
	 *
	 * @var array lines of working copy file
	 */
	protected $file1Lines;

	/**
	 * array of modifications of working copy file.
	 * one entry can be either "deleted", "added", "inexistent" or "content" (no changes)
	 *
	 * @var array modifications of file1 (working copy file)
	 */
	protected $file1Modifications;

	/**
	 * array of modifications of working base file.
	 * one entry can be either "deleted", "added", "inexistent" or "content" (no changes)
	 *
	 * @var array modifications of file1 (working base file)
	 */
	protected $file2Modifications;

	/**
	 * line counter for working copy file
	 *
	 * @var int current line of working copy file
	 */
	protected $file1CurrentLine;

	/**
	 * line counter for working base file
	 *
	 * @var int current line of working base file
	 */
	protected $file2CurrentLine;

	/**
	 * Array of all chunks found through the regular expression
	 * @see findChunks()
	 *
	 * @var array chunks returned by findChunks()
	 */
	protected $chunks = array();

	/**
	 * @return array modifications of file1 (working copy file)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getFile1Modifications() {
		return $this->file1Modifications;
	}

	/**
	 * @return array modifications of file1 (working base file)
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function getFile2Modifications() {
		return $this->file2Modifications;
	}

	/**
	 * Main method to start parsing the unified diff
	 * afterwards all changes are stored in file1Modifications/file2Modifications.
	 *
	 * @param string $patch unified diff (returned by "svn diff")
	 * @param string $file contents of the working copy file
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	public function start($patch, $file) {
		$this->patch = $patch;
		$this->chunks = $this->findChunks($patch);
		$this->file1Modifications = array();
		$this->file2Modifications = array();
		$this->file1CurrentLine = 0;
		$this->file2CurrentLine = 0;
		$this->file1Lines = $this->explodeLines($file);
		$file1LinesCount = count($this->file1Lines);
		if (isset($this->chunks[0])) {
			$this->parseChunk($this->chunks[0]);
		}
		while (count($this->file1Modifications) < $file1LinesCount) {
			$this->file1CurrentLine = $this->file1CurrentLine + 1;
			$this->file2CurrentLine = $this->file2CurrentLine + 1;
			if (isset($this->chunks[$this->file1CurrentLine])) {
				$this->parseChunk($this->chunks[$this->file1CurrentLine]);
			} else {
				if ($this->file1CurrentLine >= $file1LinesCount) {
					break;
				}
				$this->file1Modifications[] = array(
					'type' => 'content',
					'content' => $this->file1Lines[$this->file1CurrentLine],
					'line' => $this->file1CurrentLine
				);
				$this->file2Modifications[] = array(
					'type' => 'content',
					'content' => $this->file1Lines[$this->file1CurrentLine],
					'line' => $this->file2CurrentLine
				);
			}
		}
	}

	/**
	 * Walks through each line of a chunk and adds entries to file1Modifications/file2Modifications based on the modification type which is determined by the first character of each line
	 * " " -> no modifications
	 * "-" -> line deleted
	 * "+" -> line added
	 *
	 * @param string $chunk this is the part of a unified diff between the lines "@@ -a,b +c,d @@"
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function parseChunk($chunk) {
		for ($line = 0; $line < count($chunk['content']); $line++) {
			$modType = substr($chunk['content'][$line], 0, 1);
			$content = substr($chunk['content'][$line], 1);
			switch($modType) {
				case ' ':
					$this->file1Modifications[] = array(
						'type' => 'content',
						'content' => $content,
						'line' => $this->file1CurrentLine
					);
					$this->file2Modifications[] = array(
						'type' => 'content',
						'content' => $content,
						'line' => $this->file2CurrentLine
					);
					$this->file1CurrentLine = $this->file1CurrentLine + 1;
					$this->file2CurrentLine = $this->file2CurrentLine + 1;
					break;
				case '-':
					$this->file1Modifications[] = array(
						'type' => 'deleted',
						'content' => $content,
						'line' => $this->file1CurrentLine
					);
					$this->file1CurrentLine = $this->file1CurrentLine + 1;
					$this->file2Modifications[] = array(
						'type' => 'inexistent'
					);
					break;
				case '+':
					$this->file1Modifications[] = array(
						'type' => 'inexistent'
					);
					$this->file2Modifications[] = array(
						'type' => 'added',
						'content' => $content,
						'line' => $this->file2CurrentLine
					);
					$this->file2CurrentLine = $this->file2CurrentLine + 1;
					break;
			}
		}
	}

	/**
	 * uses preg_match_all and the specified regular expression pattern (PATTERN_CHUNKS) to find all chunks in the unified diff
	 * 
	 * @param string $patch the unified diff
	 * @return void
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function findChunks($patch) {
		$chunks = array();
		$matchesChunks = array();
		preg_match_all(self::PATTERN_CHUNKS, $patch, $matchesChunks);
		for ($i = 0; $i < count($matchesChunks['start1']); $i++) {
			$chunks[$matchesChunks['start1'][$i]] = array(
				'start1' => $matchesChunks['start1'][$i],
				'start2' => $matchesChunks['start2'][$i],
				'content' => $this->explodeLines($matchesChunks['content'][$i])
			);
		}
		return $chunks;
	}

	/**
	 * turns a string into an array where each line is one array entry independent from the line ending mode (UNIX/windows)
	 *
	 * @param string $content file contents to split into array entries
	 * @return array array, containing one line per entry
	 * @author Bastian Waidelich <waidelich@network-publishing.de>
	 */
	protected function explodeLines($content) {
		$content = str_replace("\r\n", "\n", str_replace("\n\r", "\n", $content));
		$content = trim($content);
		return explode(chr(10), $content);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_diffparser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/np_subversion/class.tx_npsubversion_diffparser.php']);
}
?>