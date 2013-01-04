<?php
/*
 * @deprecated since version >1.1.3, this class will be removed in later versions.
*/
class user_webkitpdf {
	/*
	 * @deprecated since version >1.1.3, this function will be removed in later versions.
	*/
	public function user_getPDFLink($content, $conf) {
		t3lib_div::deprecationLog('EXT:webkitpdf: using the userfunc user_webkitpdf->user_getPDFLink in TypoScript is deprecated since webkitpdf versions >1.1.3. This function will be removed in later versions. Please use plugin.tx_webkit_pi1.pdfLink instead and refer to the manual.');

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