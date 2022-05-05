<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['webkitpdf_pi1'] = 'layout,pages,select_key';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['WebKit PDF', 'webkitpdf_pi1'], 'list_type', 'webkitpdf');
