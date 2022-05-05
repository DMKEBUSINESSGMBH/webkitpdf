<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('webkitpdf', 'Configuration/TypoScript/', 'WebKit PDF');
