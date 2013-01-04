<?php

class tx_webkitpdf_cache {
	public function clearCachePostProc(&$params, &$pObj) {
		if($params['cacheCmd']) {
			$now = time();
			
			//cached files older than x minutes.
			$minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['cacheThreshold'];
			$threshold = $now - $minutes * 60;
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,crdate,filename', 'tx_webkitpdf_cache', 'crdate<' . $threshold);
			if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
				$filenames = array();
				while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
					$filenames[] = $row['filename'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_webkitpdf_cache', 'crdate<' . $threshold);
				foreach($filenames as $file) {
					if(file_exists($file)) {
						unlink($file);
					}
				}
				// Write a message to devlog
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['debug'] === 1) {
					t3lib_div::devLog('Clearing cached files older than ' . $minutes . ' minutes.', 'webkitpdf', -1);
				}
			}
		}
	}
	
	public function isInCache($urls) {
		$found = FALSE;
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_webkitpdf_cache', "urls='" . md5($urls) . "'");
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) === 1) {
			$found = TRUE;
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $found;
	}
	
	public function store($urls, $filename) {
		$minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['cacheThreshold'];
		if(intval($minutes) > 0) {
			$insertFields = array(
				'crdate' => time(),
				'filename' => $filename,
				'urls' => md5($urls)
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_webkitpdf_cache', $insertFields);
		}
	}
	
	public function get($urls) {
		$filename = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('filename', 'tx_webkitpdf_cache', "urls='" . md5($urls) . "'");
		if($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) === 1) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$filename = $row['filename'];
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $filename;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/res/class.tx_webkitpdf_cache.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/res/class.tx_webkitpdf_cache.php']);
}

?>
