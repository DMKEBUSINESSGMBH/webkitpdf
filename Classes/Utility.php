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
     * Escapes a URI resource name so it can safely be used on the command line.
     *
     * @param   string  $inputName URI resource name to safeguard, must not be empty
     *
     * @return  string  $inputName escaped as needed
     */
    public function wrapUriName($inputName)
    {
        return escapeshellarg($inputName);
    }

    /**
     * Checks if the given URL's host matches the current host
     * and sanitizes the URL to be used on command line.
     *
     * @param   string $url The URL to be sanitized
     * @param   array $allowedHosts
     *
     * @return string The sanitized URL
     *
     * @throws Exception
     */
    public function sanitizeUrl($url, $allowedHosts)
    {
        // Make sure that host of the URL matches TYPO3 host or one of allowed hosts set in TypoScript.
        $parts = parse_url($url);
        if ($parts['host'] !== GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY')) {
            if (($allowedHosts && !in_array($parts['host'], $allowedHosts)) || !$allowedHosts) {
                throw new \Exception('Host "'.$parts['host'].'" does not match TYPO3 host.');
            }
        }
        $url = $this->wrapUriName($url);

        return $url;
    }

    public static function debugLogging(string $title, array $dataVar = []): void
    {
        if (1 === $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['debug']) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger('webkitpdf')->debug($title, $dataVar);
        }
    }

    /**
     * Makes sure that given path has a slash as first and last character.
     *
     * @param   string      $path: The path to be sanitized
     *
     * @return  Sanitized path
     */
    public static function sanitizePath($path, $trailingSlash = true)
    {
        // slash as last character
        if ($trailingSlash && '/' !== substr($path, (strlen($path) - 1))) {
            $path .= '/';
        }

        // slash as first character
        if ('/' !== substr($path, 0, 1)) {
            $path = '/'.$path;
        }

        return $path;
    }

    /**
     * Generates a random hash.
     *
     * @return  The generated hash
     */
    public static function generateHash()
    {
        $result = '';
        $charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($p = 0; $p < 15; ++$p) {
            $result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
        }

        return sha1(md5(sha1($result)));
    }
}
