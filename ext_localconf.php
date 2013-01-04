<?php
if(!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_webkitpdf_pi1.php', '_pi1', 'list_type', 1);

// Clear cache
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$_EXTKEY] = 'EXT:webkitpdf/res/class.tx_webkitpdf_cache.php:&tx_webkitpdf_cache->clearCachePostProc';

// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheThreshold'] = intval($_EXTCONF['cacheThreshold']);

?>
