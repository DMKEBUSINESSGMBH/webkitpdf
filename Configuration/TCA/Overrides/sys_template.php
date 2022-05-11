<?php

if (!defined('TYPO3')) {
    exit('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('webkitpdf', 'Configuration/TypoScript/', 'WebKit PDF');
