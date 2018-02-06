<?php
namespace DMK\Webkitpdf;

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
 * DMK\Webkitpdf$Utility
 *
 * @package         TYPO3
 * @subpackage      webkitpdf
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
     * @throws Exception
     */
    public function sanitizeURL($url, $allowedHosts)
    {

        //Make sure that host of the URL matches TYPO3 host or one of allowed hosts set in TypoScript.
        $parts = parse_url($url);
        if ($parts['host'] !== \tx_rnbase_util_Misc::getIndpEnv('TYPO3_HOST_ONLY')) {
            if (($allowedHosts && !in_array($parts['host'], $allowedHosts)) || !$allowedHosts) {
                throw new \Exception('Host "' . $parts['host'] . '" does not match TYPO3 host.');
            }
        }
        $url = $this->wrapUriName($url);

        return $url;
    }

    /**
     * Appends information about the FE user session to the URL.
     * This is used to be able to generate PDFs of access restricted pages.
     *
     * @param   string  $url The URL to append the parameters to
     * @return  string  The processed URL
     */
    public function appendFESessionInfoToURL($url)
    {
        if (strpos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        $url .= 'FE_SESSION_KEY=' .
                rawurlencode(
                    $GLOBALS['TSFE']->fe_user->id .
                    '-' .
                    md5(
                        $GLOBALS['TSFE']->fe_user->id .
                        '/' .
                        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
                    )
                );

        return $url;
    }

    /**
     * Writes log messages to devLog
     *
     * Acts as a wrapper for tx_rnbase_util_Logger::devLog()
     * Additionally checks if debug was activated
     *
     * @param   string      $title: title of the event
     * @param   string      $severity: severity of the debug event
     * @param   array       $dataVar: additional data
     * @return  void
     */
    public static function debugLogging($title, $severity = -1, $dataVar = array())
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['debug'] === 1) {
            \tx_rnbase_util_Logger::devlog($title, 'webkitpdf', $severity, $dataVar);
        }
    }

    /**
     * Makes sure that given path has a slash as first and last character
     *
     * @param   string      $path: The path to be sanitized
     * @return  Sanitized path
     */
    public static function sanitizePath($path, $trailingSlash = true)
    {

        // slash as last character
        if ($trailingSlash && substr($path, (strlen($path) - 1)) !== '/') {
            $path .= '/';
        }

        //slash as first character
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Generates a random hash
     *
     * @return  The generated hash
     */
    public static function generateHash()
    {
        $result = '';
        $charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($p = 0; $p < 15; $p++) {
            $result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
        }

        return sha1(md5(sha1($result)));
    }
}
