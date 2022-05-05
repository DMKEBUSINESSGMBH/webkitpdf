<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('webkitpdf', 'Classes/Plugin.php', '_pi1', 'list_type', 0);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['webkitpdf'] =
    \DMK\Webkitpdf\Cache::class.'->clearWebkitPdfCache';
