<?php

namespace DMK\Webkitpdf;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

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
 * DMK\Webkitpdf$Plugin.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class Plugin extends AbstractPlugin
{
    /**
     * @var string
     */
    public $prefixId = 'tx_webkitpdf_pi1';

    /**
     * @var string
     */
    public $extKey = 'webkitpdf';

    /**
     * @var \DMK\Webkitpdf\Cache
     */
    protected $cacheManager;

    /**
     * @var string
     */
    protected $scriptPath;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var string
     */
    protected $paramName;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $filenameOnly;

    /**
     * @var string
     */
    protected $contentDisposition;

    /**
     * @var string
     */
    protected $scriptCall;

    /**
     * @var string
     */
    protected $scriptCallOutput;

    public function main(string $content, array $conf): string
    {
        $this->init($conf);
        $urls = $this->getUrls();

        if (!empty($urls) && count($urls) > 0) {
            $urls = $this->sanitizeUrls($urls);

            $this->initializeFileNameToOfferAsDownload($urls);

            if (1 == ($this->conf['fileOnly'] ?? 0)) {
                return $this->filename;
            }

            if (!$this->pdfExists()) {
                $this->handlePdfExistsNot();
            } else {
                $this->offerPdfForDownload();
            }
        }

        return $this->pi_wrapInBaseClass('');
    }

    protected function init(array $conf): void
    {
        // Process stdWrap properties
        $temp = $conf['scriptParams.'] ?? '';
        unset($conf['scriptParams.']);
        $this->conf = $this->processStdWraps($conf);
        if (is_array($temp)) {
            $this->conf['scriptParams'] = $this->processStdWraps($temp);
        }

        $this->pi_setPiVarDefaults();

        $this->scriptPath = $this->addSlashesToPath(
            $this->conf['customScriptPath'] ?? ExtensionManagementUtility::extPath('webkitpdf').'Resources/Private/Binaries/'
        );
        $this->outputPath = $this->addSlashesToPath(
            Environment::getPublicPath().($this->conf['customTempOutputPath'] ?? '/typo3temp/tx_webkitpdf/')
        );

        if (!is_dir($this->outputPath)) {
            GeneralUtility::mkdir_deep($this->outputPath);
        }

        $this->paramName = 'urls';
        if ($this->conf['customParameterName'] ?? '') {
            $this->paramName = $this->conf['customParameterName'];
        }

        $this->filename = $this->outputPath.($this->conf['filePrefix'] ?? '').Utility::generateHash().'.pdf';
        $this->filenameOnly = basename($this->filename);
        if ($this->conf['staticFileName'] ?? '') {
            $this->filenameOnly = $this->conf['staticFileName'];
        }

        if ('.pdf' !== substr($this->filenameOnly, strlen($this->filenameOnly) - 4)) {
            $this->filenameOnly .= '.pdf';
        }

        $this->readScriptSettings();
        $this->cacheManager = GeneralUtility::makeInstance(Cache::class, $this->conf);

        $this->contentDisposition = 'attachment';
        if (1 === intval($this->conf['openFilesInline'] ?? 0)) {
            $this->contentDisposition = 'inline';
        }

        $this->initDosAttackPrevention();
    }

    /**
     * Wenn wir unbegrenzt viele URLs zu lassen, dann besteht die Gefahr
     * dass der Server auf sich selbst eine DoS Attacke ausführt, indem eine
     * große Anzahl von URLs übergeben wird.
     */
    protected function initDosAttackPrevention(): void
    {
        if ($this->conf['numberOfUrlsAllowedToProcess'] ?? false) {
            $this->makeSureNotMoreUrlsAreProcessedThanAllowed();
        }
    }

    protected function makeSureNotMoreUrlsAreProcessedThanAllowed(): void
    {
        if (is_array($this->piVars[$this->paramName] ?? null)) {
            $this->piVars[$this->paramName] = array_slice(
                $this->piVars[$this->paramName],
                0,
                intval($this->conf['numberOfUrlsAllowedToProcess'])
            );
        }
    }

    protected function initializeFileNameToOfferAsDownload(array $urls): void
    {
        $originalUrls = implode(' ', $urls);
        if ($GLOBALS['TSFE']->getContext()->getAspect('frontend.user')->isLoggedIn()
            || !$this->cacheManager->isInCache($originalUrls)
            || '1' === ($this->conf['debugScriptCall'] ?? false)
        ) {
            $this->generatePdf($urls, $originalUrls);
        } else {
            $this->filename = $this->cacheManager->get($originalUrls);
        }
    }

    protected function getUrls(): array
    {
        $urls = $this->piVars[$this->paramName] ?? [];
        if (!$urls) {
            if (isset($this->conf['urls.'])) {
                $urls = $this->conf['urls.'];
            } else {
                $urls = $this->conf['urls'] ? [$this->conf['urls']] : [];
            }
        }

        return $urls;
    }

    protected function sanitizeUrls(array $urls): array
    {
        $allowedHosts = [];
        if ($this->conf['allowedHosts'] ?? '') {
            $allowedHosts = GeneralUtility::trimExplode(',', $this->conf['allowedHosts']);
        }

        $utility = $this->getUtility();

        foreach ($urls as &$url) {
            $url = $utility->sanitizeUrl($url, $allowedHosts);
        }

        return $urls;
    }

    protected function getUtility(): Utility
    {
        return new Utility();
    }

    protected function generatePdf(array $urls, string $origUrls): void
    {
        $this->scriptCall =
            escapeshellcmd($this->scriptPath.'wkhtmltopdf').' '.
            $this->buildScriptOptions().' '.
            implode(' ', $urls).' '.
            escapeshellarg($this->filename).
            ' 2>&1';

        $this->callExec();

        // Write debugging information to devLog
        Utility::debugLogging(
            'Shell command debug',
            [
                'scriptCall' => $this->scriptCall,
                'scriptCallOutput' => $this->scriptCallOutput,
            ]
        );

        if ($this->pdfExists()) {
            $this->cacheManager->store($origUrls, $this->filename);
        }
    }

    protected function callExec(): void
    {
        exec($this->scriptCall, $this->scriptCallOutput);
    }

    protected function pdfExists(): bool
    {
        return file_exists($this->filename) && filesize($this->filename);
    }

    /**
     * @todo write unit tests
     */
    protected function handlePdfExistsNot(): void
    {
        GeneralUtility::makeInstance(LogManager::class)->getLogger('webkitpdf')->warning(
            'PDF was not created successfully',
            [
                'Executed shell command' => $this->scriptCall,
                'Output of shell command' => $this->scriptCallOutput,
                '$_GET' => $_GET,
                '$_POST' => $_POST,
                '$_SERVER' => $_SERVER,
            ]
        );

        $message = 'webkitpdf could not create the PDF file for the desired page. Check the devlog for more information.';
        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
            $GLOBALS['TYPO3_REQUEST'],
            $message
        );
        throw new ImmediateResponseException($response, 1590468229);
    }

    /**
     * @todo write unit tests
     */
    protected function offerPdfForDownload(): void
    {
        header('Content-type: application/pdf');
        header('Content-Transfer-Encoding: Binary');
        header('Content-Length: '.filesize($this->filename));
        header('Content-Disposition: '.$this->contentDisposition.'; filename="'.$this->filenameOnly.'"');
        header('X-Robots-Tag: noindex');
        readfile($this->filename);

        if (!$this->cacheManager->isCachingEnabled()) {
            unlink($this->filename);
        }

        exit(0);
    }

    protected function readScriptSettings(): array
    {
        $defaultSettings = [
            'footer-right' => '[page]/[toPage]',
            'footer-font-size' => '6',
            'header-font-size' => '6',
            'margin-left' => '15mm',
            'margin-right' => '15mm',
            'margin-top' => '15mm',
            'margin-bottom' => '15mm',
        ];

        $tsSettings = $this->conf['scriptParams'] ?? [];
        foreach ($defaultSettings as $param => $value) {
            if (!isset($tsSettings[$param])) {
                $tsSettings[$param] = $value;
            }
        }

        $finalSettings = [];
        foreach ($tsSettings as $param => $value) {
            $value = trim($value);
            if ('--' !== substr($param, 0, 2)) {
                $param = '--'.$param;
            }
            $finalSettings[$param] = $value;
        }

        return $finalSettings;
    }

    protected function buildScriptOptions(): string
    {
        $options = [];
        if ($this->conf['pageURLInHeader'] ?? '') {
            $options['--header-center'] = '[webpage]';
        }

        if ($this->conf['copyrightNotice'] ?? '') {
            $options['--footer-left'] = '© '.date('Y', time()).$this->conf['copyrightNotice'].'';
        }

        if ($this->conf['additionalStylesheet'] ?? '') {
            $this->conf['additionalStylesheet'] = $this->addSlashesToPath($this->conf['additionalStylesheet'], false);
            $options['--user-style-sheet'] = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST').$this->conf['additionalStylesheet'];
        }

        $userSettings = $this->readScriptSettings();
        $options = array_merge($options, $userSettings);

        $paramsString = '';
        foreach ($options as $param => $value) {
            if (strlen($value) > 0) {
                $value = escapeshellarg($value);
            }
            $paramsString .= ' '.$param.' '.$value;
        }

        foreach ($_COOKIE as $cookieName => $cookieValue) {
            $paramsString .= ' --cookie '.escapeshellarg($cookieName).' '.escapeshellarg($cookieValue);
        }

        return $paramsString;
    }

    protected function addSlashesToPath(string $path, bool $trailingSlash = true): string
    {
        // slash as last character
        if ($trailingSlash && '/' !== substr($path, strlen($path) - 1)) {
            $path .= '/';
        }

        // slash as first character
        if ('/' !== substr($path, 0, 1)) {
            $path = '/'.$path;
        }

        return $path;
    }

    protected function processStdWraps(array $tsSettings): array
    {
        // Get TS values and process stdWrap properties
        foreach ($tsSettings as $key => $value) {
            $process = true;
            if ('.' === substr($key, -1)) {
                $key = substr($key, 0, -1);
                if (array_key_exists($key, $tsSettings)) {
                    $process = false;
                }
            }

            if (('.' === substr($key, -1) && !array_key_exists(substr($key, 0, -1), $tsSettings)) ||
                ('.' !== substr($key, -1) && array_key_exists($key.'.', $tsSettings)) && !strstr($key, 'scriptParams')) {
                $tsSettings[$key] = $this->cObj->stdWrap($value, $tsSettings[$key.'.']);

                // Remove the additional TS properties after processing, otherwise they'll be translated to pdf properties
                if (isset($tsSettings[$key.'.'])) {
                    unset($tsSettings[$key.'.']);
                }
            }
        }

        return $tsSettings;
    }
}
