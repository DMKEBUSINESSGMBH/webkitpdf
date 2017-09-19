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
 * DMK\Webkitpdf$Cache
 *
 * @package         TYPO3
 * @subpackage      webkitpdf
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class Cache
{

    /**
     * @var array
     */
    protected $conf;

    /**
     * @var boolean
     */
    protected $isEnabled;

    /**
     * @param array $conf
     */
    public function __construct(array $conf = array())
    {
        $this->conf = $conf;
        $this->isEnabled = true;
        $minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['cacheThreshold'];
        if (intval($minutes) === 0) {
            $this->isEnabled = false;
        }
        if (intval($this->conf['disableCache']) === 1) {
            $this->isEnabled = false;
        }
    }

    /**
     * @return void
     */
    public function clearWebkitPdfCache()
    {
        $now = time();

        //cached files older than x minutes.
        $minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['cacheThreshold'];
        $threshold = $now - $minutes * 60;

        $databaseConnection = \Tx_Rnbase_Database_Connection::getInstance();
        $rows = $databaseConnection->doSelect(
            'filename', 'tx_webkitpdf_cache',
            array('where' => 'crdate<' . $threshold, 'enablefieldsoff' => true)
        );
        if (empty($rows)) {
            $filenames = array();
            foreach ($rows as $row) {
                if (file_exists($row['filename'])) {
                    unlink($row['filename']);
                }
            }
            $databaseConnection->doDelete('tx_webkitpdf_cache', 'crdate<' . $threshold);

            // Write a message to devlog
            if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['debug'] === 1) {
                \tx_rnbase_util_Logger::devLog('Clearing cached files older than ' . $minutes . ' minutes.', 'webkitpdf', -1);
            }
        }
    }

    /**
     * @param string $urls
     * @return boolean
     */
    public function isInCache(string $urls) : boolean
    {
        $found = false;
        if ($this->isEnabled) {
            $databaseConnection = \Tx_Rnbase_Database_Connection::getInstance();
            $rows = $databaseConnection->doSelect(
                'uid', 'tx_webkitpdf_cache',
                array('where' => 'urls=' . $databaseConnection->fullQuoteStr(md5($urls)), 'enablefieldsoff' => true)
            );
            $found = count($rows) > 0;
        }

        return $found;
    }

    /**
     * @param string $urls
     * @param string $filename
     */
    public function store(string $urls, string $filename)
    {
        if ($this->isEnabled) {
            $insertFields = array(
                'crdate' => time(),
                'filename' => $filename,
                'urls' => md5($urls)
            );
            \Tx_Rnbase_Database_Connection::getInstance()->doInsert('tx_webkitpdf_cache', $insertFields);
        }
    }

    /**
     * @param string $urls
     * @return string
     */
    public function get(string $urls) : string
    {
        $filename = '';
        if ($this->isEnabled) {
            $databaseConnection = \Tx_Rnbase_Database_Connection::getInstance();
            $rows = $databaseConnection->doSelect(
                'filename', 'tx_webkitpdf_cache',
                array(
                    'where' => 'urls=' . $databaseConnection->fullQuoteStr(md5($urls)),
                    'enablefieldsoff' => true, 'limit' => 1
                )
            );
            if ($rows) {
                $filename = $rows[0]['filename'];
            }
        }

        return $filename;
    }

    /**
     * @return boolean
     */
    public function isCachingEnabled() : boolean
    {
        return $this->isEnabled;
    }
}
