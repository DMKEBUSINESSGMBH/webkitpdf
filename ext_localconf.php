<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

tx_rnbase_util_Extensions::addPItoST43($_EXTKEY, 'Classes/Plugin.php', '_pi1', 'list_type', 0);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['webkitpdf'] =
    \DMK\Webkitpdf\Cache::class . '->clearWebkitPdfCache';

// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheThreshold'] = intval($_EXTCONF['cacheThreshold']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debug'] = intval($_EXTCONF['debug']);

Tx_Rnbase_Utility_Cache::addExcludedParametersForCacheHash(array(
    // parameter added when a user is logged in
    'FE_SESSION_KEY'
));
// realurl
if (tx_rnbase_util_Extensions::isLoaded('realurl')) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['ConfigurationReader_postProc']['webkitpdf'] =
        \DMK\Webkitpdf\Hooks\RealUrlConfigurationReader::class.'->addWebkitpdfToBannedUrlsRegExp';
}

