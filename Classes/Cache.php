<?php

namespace DMK\Webkitpdf;

use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
 * DMK\Webkitpdf$Cache.
 *
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
     * @var bool
     */
    protected $isEnabled;

    /**
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        $this->conf = $conf;
        $this->isEnabled = true;
        $minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['cacheThreshold'] ?? 0;
        if (0 === intval($minutes)) {
            $this->isEnabled = false;
        }
        if (1 === intval($this->conf['disableCache'] ?? 0)) {
            $this->isEnabled = false;
        }
    }

    /**
     * @return void
     */
    public function clearWebkitPdfCache()
    {
        $now = time();

        // cached files older than x minutes.
        $minutes = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['cacheThreshold'] ?? 0;
        $threshold = $now - $minutes * 60;

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->where(
            $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($threshold, \PDO::PARAM_INT))
        );
        $deleteQueryBuilder = clone $queryBuilder;
        $result = $queryBuilder
            ->select('filename')
            ->from('tx_webkitpdf_cache')
            ->execute();
        while ($row = $result->fetchAssociative()) {
            if (file_exists($row['filename'])) {
                unlink($row['filename']);
            }
        }
        $deleteQueryBuilder
            ->delete('tx_webkitpdf_cache')
            ->execute();

        Utility::debugLogging('Clearing cached files older than '.$minutes.' minutes.');
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_webkitpdf_cache');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }

    /**
     * @param string $urls
     *
     * @return bool
     */
    public function isInCache(string $urls): bool
    {
        $found = false;
        if ($this->isEnabled) {
            $queryBuilder = $this->getQueryBuilder();
            $result = $queryBuilder
                ->select('uid')
                ->from('tx_webkitpdf_cache')
                ->where(
                    $queryBuilder->expr()->eq('urls', $queryBuilder->createNamedParameter(md5($urls), \PDO::PARAM_STR))
                )
                ->execute();
            $found = $result->rowCount() > 0;
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
            $this->getQueryBuilder()
                ->insert('tx_webkitpdf_cache')
                ->values([
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'filename' => $filename,
                    'urls' => md5($urls),
                ])
                ->execute();
        }
    }

    /**
     * @param string $urls
     *
     * @return string
     */
    public function get(string $urls): string
    {
        $filename = '';
        if ($this->isEnabled) {
            $queryBuilder = $this->getQueryBuilder();
            $result = $queryBuilder
                ->select('filename')
                ->from('tx_webkitpdf_cache')
                ->where(
                    $queryBuilder->expr()->eq('urls', $queryBuilder->createNamedParameter(md5($urls), \PDO::PARAM_STR))
                )
                ->setMaxResults(1)
                ->execute();
            $filename = $result->fetchOne() ?? '';
        }

        return $filename;
    }

    /**
     * @return bool
     */
    public function isCachingEnabled(): bool
    {
        return $this->isEnabled;
    }
}
