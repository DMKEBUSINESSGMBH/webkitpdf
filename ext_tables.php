<?php
if(!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Add static file for plugin
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'WebKit PDF');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,pages,select_key';

t3lib_extMgm::addPlugin(array('LLL:EXT:webkitpdf/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');

?>
