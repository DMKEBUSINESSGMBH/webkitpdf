<?php

class user_webkitpdf {
	
	public function user_getPDFLink($content, $conf) {
		
		$pid = $GLOBALS['TSFE']->id;
		if($conf['pid']) {
			$pid = $conf['pid'];
		}
		
		$text = 'Save as PDF';
		if($conf['linkText'] && $conf['linkText.']) {
			$text = $this->cObj->cObjGetSingle($conf['linkText'], $conf['linkText.']);
		} elseif($conf['linkText']) {
			$text = $conf['linkText'];
		}
		
		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		
		$params = array(
			
			'no_cache' => 1,
			'tx_webkitpdf_pi1' => array(
				'urls' => array(
					$url
				)
			)
		);
		
		return $this->cObj->getTypolink($text, $pid, $params);
		
	}
	
}

?>