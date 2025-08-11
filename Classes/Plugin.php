<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "webkitpdf" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace DMK\Webkitpdf;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;

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
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class Plugin
{
    protected array $requestParameters;

    /**
     * @var Cache
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
    protected $requestParameterName;

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
     * @var array
     */
    protected $scriptCallOutput;

    protected array $conf;

    protected readonly ContentObjectRenderer $contentObjectRenderer;

    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function setContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer): void
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function main(string $content, array $conf, ServerRequestInterface $request): string
    {
        $this->init($conf, $request);
        $urls = $this->getUrls();

        if ([] !== $urls) {
            $urls = $this->sanitizeUrls($urls);

            $this->initializeFileNameToOfferAsDownload($urls);

            if (1 == ($this->conf['fileOnly'] ?? 0)) {
                return $this->filename;
            }

            if ($this->pdfExists()) {
                $this->offerPdfForDownload();
            }

            $this->handlePdfExistsNot();
        }

        return '';
    }

    protected function init(array $conf, ServerRequestInterface $request): void
    {
        $this->requestParameters = $request->getQueryParams()['tx_webkitpdf_pi1'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($this->requestParameters, $request->getParsedBody()['tx_webkitpdf_pi1'] ?? []);

        // Process stdWrap properties
        $temp = $conf['scriptParams.'] ?? '';
        unset($conf['scriptParams.']);
        $this->conf = $this->processStdWraps($conf);
        if (is_array($temp)) {
            $this->conf['scriptParams'] = $this->processStdWraps($temp);
        }

        $this->scriptPath = $this->surroundWithSlashes(
            GeneralUtility::getFileAbsFileName(
                $this->conf['customScriptPath'] ?? ExtensionManagementUtility::extPath('webkitpdf').'Resources/Private/Binaries/'
            )
        );
        $this->outputPath = $this->surroundWithSlashes(
            Environment::getPublicPath().($this->conf['customTempOutputPath'] ?? '/typo3temp/tx_webkitpdf/')
        );

        if (!is_dir($this->outputPath)) {
            GeneralUtility::mkdir_deep($this->outputPath);
        }

        $this->requestParameterName = 'urls';
        if ($this->conf['customParameterName'] ?? '') {
            $this->requestParameterName = $this->conf['customParameterName'];
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
        if (is_array($this->requestParameters[$this->requestParameterName] ?? null)) {
            $this->requestParameters[$this->requestParameterName] = array_slice(
                $this->requestParameters[$this->requestParameterName],
                0,
                intval($this->conf['numberOfUrlsAllowedToProcess'])
            );
        }
    }

    /**
     * @SuppressWarnings("PHPMD.ElseExpression")
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function initializeFileNameToOfferAsDownload(array $urls): void
    {
        $originalUrls = implode(' ', $urls);
        if ($this->context->getAspect('frontend.user')->isLoggedIn()
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
        return $this->requestParameters[$this->requestParameterName]
            ?? $this->conf['urls.']
            ?? ($this->conf['urls'] ? [$this->conf['urls']] : []);
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
     *
     * @SuppressWarnings("PHPMD.Superglobals")
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
     *
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    protected function offerPdfForDownload(): void
    {
        if (!$this->cacheManager->isCachingEnabled()) {
            header('Expires: 0');
            header('Pragma: no-cache');
            header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        }

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
            $value = trim((string) $value);
            if (!str_starts_with((string) $param, '--')) {
                $param = '--'.$param;
            }

            $finalSettings[$param] = $value;
        }

        return $finalSettings;
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
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
            $this->conf['additionalStylesheet'] = $this->prefixWithSlash($this->conf['additionalStylesheet']);
            $options['--user-style-sheet'] = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST').$this->conf['additionalStylesheet'];
        }

        $userSettings = $this->readScriptSettings();
        $options = array_merge($options, $userSettings);

        $paramsString = '';
        foreach ($options as $param => $value) {
            if (strlen((string) $value) > 0) {
                $value = escapeshellarg((string) $value);
            }

            $paramsString .= ' '.$param.' '.$value;
        }

        foreach ($_COOKIE as $cookieName => $cookieValue) {
            $paramsString .= ' --cookie '.escapeshellarg($cookieName).' '.escapeshellarg((string) $cookieValue);
        }

        return $paramsString;
    }

    protected function surroundWithSlashes(string $path): string
    {
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $this->prefixWithSlash($path);
    }

    protected function prefixWithSlash(string $path): string
    {
        if (!str_starts_with($path, '/')) {
            return '/'.$path;
        }

        return $path;
    }

    protected function processStdWraps(array $tsSettings): array
    {
        // Get TS values and process stdWrap properties
        foreach ($tsSettings as $key => $value) {
            if (str_ends_with($key, '.')) {
                $key = substr($key, 0, -1);
            }

            if (
                (
                    str_ends_with($key, '.')
                    && !array_key_exists(substr($key, 0, -1), $tsSettings)
                )
                || (
                    !str_ends_with($key, '.')
                    && array_key_exists($key.'.', $tsSettings)
                )
                && !str_contains($key, 'scriptParams')
            ) {
                $tsSettings[$key] = $this->contentObjectRenderer->stdWrap($value, $tsSettings[$key.'.']);

                // Remove the additional TS properties after processing, otherwise they'll be translated to pdf properties
                if (isset($tsSettings[$key.'.'])) {
                    unset($tsSettings[$key.'.']);
                }
            }
        }

        return $tsSettings;
    }
}
