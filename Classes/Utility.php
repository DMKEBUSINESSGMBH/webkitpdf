<?php

namespace DMK\Webkitpdf;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <kontakt@dmk-ebusiness.de>
 * All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * DMK\Webkitpdf$Utility.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class Utility
{
    /**
     * Checks if the given URL's host matches the current host
     * and sanitizes the URL to be used on command line.
     *
     * @throws Exception
     */
    public function sanitizeUrl(string $url, array $allowedHosts): string
    {
        // Make sure that host of the URL matches TYPO3 host or one of allowed hosts set in TypoScript.
        $parts = parse_url($url);
        if ($parts['host'] !== GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY')) {
            if (($allowedHosts && !in_array($parts['host'], $allowedHosts)) || !$allowedHosts) {
                throw new \Exception('Host "'.$parts['host'].'" does not match TYPO3 host.');
            }
        }

        return escapeshellarg($url);
    }

    public static function debugLogging(string $title, array $dataVar = []): void
    {
        if (1 === $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['debug']) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger('webkitpdf')->debug($title, $dataVar);
        }
    }

    public static function generateHash(): string
    {
        $result = '';
        $charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($p = 0; $p < 15; ++$p) {
            $result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
        }

        return sha1(md5(sha1($result)));
    }
}
