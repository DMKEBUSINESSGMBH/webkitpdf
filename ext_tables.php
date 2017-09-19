<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Add static file for plugin
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'WebKit PDF');

tx_rnbase_util_TCA::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,pages,select_key';

tx_rnbase_util_Extensions::addPlugin(array('LLL:EXT:webkitpdf/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');
