<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
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
 * Plugin 'WebKit PDFs' for the 'webkitpdf' extension.
 *
 * @author Reinhard Führicht <rf@typoheads.at>
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('webkitpdf') . '/res/class.tx_webkitpdf_cache.php');

class tx_webkitpdf_pi1 extends tslib_pibase {
	var $prefixId = 'tx_webkitpdf_pi1';
	var $scriptRelPath = 'pi1/class.tx_webkitpdf_pi1.php';	
	var $extKey = 'webkitpdf';	

	// Disbale caching: Don't check cHash, because the plugin is a USER_INT object
	public $pi_checkCHash = FALSE;
	public $pi_USER_INT_obj = 1;
	
	protected $cacheManager;
	protected $scriptPath;
	protected $outputPath;
	protected $paramName;
	protected $filename;
	protected $filenameOnly;
	
	/**
	 * Init parameters. Reads TypoScript settings.
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	protected function init($conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		
		$this->scriptPath = t3lib_extMgm::extPath('webkitpdf') . 'res/';
		if($this->conf['customScriptPath']) {
			$this->scriptPath = $this->conf['customScriptPath'];
		}
		$this->outputPath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
		if($this->conf['customTempOutputPath']) {
			$this->outputPath .= $this->sanitizePath($this->conf['customTempOutputPath']);
		} else {
			$this->outputPath .= '/typo3temp/tx_webkitpdf/';
		}
		
		$this->paramName = 'urls';
		if($this->conf['customParameterName']) {
			$this->paramName = $this->conf['customParameterName'];
		}
		
		$this->filename = $this->outputPath . $this->conf['filePrefix'] . $this->generateHash() . '.pdf';		
		$this->filenameOnly = basename($this->filename);
		if($this->conf['staticFileName'] && $this->conf['staticFileName.']) {
			$this->filenameOnly = $this->cObj->cObjGetSingle($this->conf['staticFileName'], $this->conf['staticFileName.']);
		} elseif($this->conf['staticFileName']) {
			$this->filenameOnly = $this->conf['staticFileName'];
		}
			
		if(substr($this->filenameOnly, strlen($this->filenameOnly) - 4) !== '.pdf') {
			$this->filenameOnly .= '.pdf';
		}
		
		$this->readScriptSettings();
		$this->cacheManager = t3lib_div::makeInstance('tx_webkitpdf_cache');
	}
	
	protected function generateHash(){
		$result = '';
		$charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
		for($p = 0; $p < 15; $p++) {
			$result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
		}
		return sha1(md5(sha1($result)));
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content,$conf)	{
		$this->init($conf);

		$urls = $this->piVars[$this->paramName];
		if(!$urls) {
			$urls = array();
			// Allow string/stdWrap in urls
			// We need to do some ugly checks for backward compatibility
			$urlsArr = $this->conf['urls.'];
			foreach ($urlsArr as $key => $value) {
				$lastChar = substr($key, strlen($key) - 1);
				if ($lastChar === '.' && is_array($value)) {
					$key = substr($key, 0, -1);
					$str = $urls[$key];
					$urls[$key] = trim($this->cObj->stdWrap($str, $value));
					unset($urlsArr[$key]);
				} else {
					$urls[$key] = $value;
				}
			}
		}

		$content = '';
		if(!empty($urls)) {
			if(count($urls) > 0) {
				
				$origUrls = implode(' ', $urls);
				
				foreach($urls as &$url) {
					$url = $this->wrapUriName($url);
				}
				
				// not in cache. generate PDF file
				if(!$this->cacheManager->isInCache($origUrls) || $this->conf['debugScriptCall'] === '1') {
					
					$scriptCall = 	$this->scriptPath. 'wkhtmltopdf ' .
									$this->buildScriptOptions() . ' ' .
									implode(' ', $urls) . ' ' .
									$this->filename .
									' 2>&1';
					
					$output = shell_exec($scriptCall);

					// Write debugging information to devLog
					$this->debugLogging('Executed shell command', -1, array($scriptCall));
					$this->debugLogging('Output of shell command', -1, array($output));
					
					$this->cacheManager->store($origUrls, $this->filename);
					
				} else {
					
					//read filepath from cache
					$this->filename = $this->cacheManager->get($origUrls);
				}
				
				if($this->conf['fileOnly'] == 1) {
					return $this->filename;
				}
				
				$filesize = filesize($this->filename);
				
				header('Content-type: application/pdf');
				header('Content-Transfer-Encoding: Binary');
				header('Content-Length: ' . $filesize);
				header('Content-Disposition: attachment; filename="' . $this->filenameOnly . '"');
				readfile($this->filename);
			}
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	protected function readScriptSettings() {
		$defaultSettings = array(
			'footer-right' => '[page]/[toPage]',
			'footer-font-size' => '6',
			'header-font-size' => '6',
			'margin-left' => '15mm',
			'margin-right' => '15mm',
			'margin-top' => '15mm',
			'margin-bottom' => '15mm',
		);
		
		$scriptParams = array();
		$tsSettings = $this->conf['scriptParams.'];
		foreach($defaultSettings as $param => $value) {
			if(!isset($tsSettings[$param])) {
				$tsSettings[$param] = $value;
			}
		}
		
		$finalSettings = array();
		foreach($tsSettings as $param => $value) {
			if(substr($param, 0, 2) !== '--') {
				$param = '--' . $param;
			}
			$finalSettings[$param] = $value;
		}
		return $finalSettings;
	}

	/**
	 * Creates the parameters for the wkhtmltopdf call.
	 *
	 * @return string The parameter string
	 */
	protected function buildScriptOptions() {
		$options = array();
		if($this->conf['pageURLInHeader']) {
			$options['--header-center'] = '[webpage]';
		}
		
		if($this->conf['copyrightNotice']) {
			$options['--footer-left'] = '" Copyright ' . date('Y', time()) . $this->conf['copyrightNotice'] . '"';
		}
		
		if($this->conf['additionalStylesheet']) {
			$this->conf['additionalStylesheet'] = $this->sanitizePath($this->conf['additionalStylesheet'], FALSE);
			$options['--user-style-sheet'] = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . $this->conf['additionalStylesheet'];
				
		}

		$userSettings = $this->readScriptSettings();
		$options = array_merge($options, $userSettings);
		
		$paramString = '';
		foreach($options as $param => $value) {
			$value = (strlen($value) > 0) ? '"' . $value . '"' : '';
			$paramsString .= ' ' . $param . ' ' . $value; 
		}
		return $paramsString;
	}

	/**
	 * Makes sure that given path has a slash as first and last character
	 *
	 * @param	string		$path: The path to be sanitized
	 * @return	Sanitized path
	 */
	protected function sanitizePath($path, $trailingSlash = TRUE) {
		
		// slash as last character
		if($trailingSlash && substr($path, (strlen($path) - 1)) !== '/') {
			$path .= '/';
		}
		
		//slash as first character
		if(substr($path, 0, 1) !== '/') {
			$path = '/' . $path;
		}
		
		return $path;
	}

	/**
	 * Writes log messages to devLog
	 *
	 * Acts as a wrapper for t3lib_div::devLog()
	 * Additionally checks if debug was activated
	 *
	 * @param	string		$title: title of the event
	 * @param	string		$severity: severity of the debug event
	 * @param	array		$dataVar: additional data
	 * @return	void
	 */
	protected function debugLogging($title, $severity = -1, $dataVar = array()) {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['debug'] === 1) {
			t3lib_div::devlog($title, $this->extKey, $severity, $dataVar);
		}
	}
	
	/**
	 * Escapes a URI resource name so it can safely be used on the command line.
	 *
	 * @param   string  $inputName URI resource name to safeguard, must not be empty
	 * @return  string  $inputName escaped as needed
	 */
	protected function wrapUriName($inputName) {
		return escapeshellarg($inputName);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/pi1/class.tx_webkitpdf_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/pi1/class.tx_webkitpdf_pi1.php']);
}

?>
