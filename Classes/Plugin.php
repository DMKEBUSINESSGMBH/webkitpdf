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
 * DMK\Webkitpdf$Plugin
 *
 * @package         TYPO3
 * @subpackage      webkitpdf
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class Plugin extends \Tx_Rnbase_Frontend_Plugin
{
    /**
     * @var string
     */
    public $prefixId = 'tx_webkitpdf_pi1';

    /**
     * @var string
     */
    public  $extKey = 'webkitpdf';

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

    /**
     * Init parameters. Reads TypoScript settings.
     *
     * @param   array       $conf: The PlugIn configuration
     * @return  void
     */
    protected function init($conf)
    {

        // Process stdWrap properties
        $temp = $conf['scriptParams.'];
        unset($conf['scriptParams.']);
        $this->conf = $this->processStdWraps($conf);
        if (is_array($temp)) {
            $this->conf['scriptParams'] = $this->processStdWraps($temp);
        }

        $this->pi_setPiVarDefaults();

        $this->scriptPath = \tx_rnbase_util_Extensions::extPath('webkitpdf') . 'Resources/Private/Binaries';
        if ($this->conf['customScriptPath']) {
            $this->scriptPath = $this->conf['customScriptPath'];
        }
        $this->outputPath = \tx_rnbase_util_Misc::getIndpEnv('TYPO3_DOCUMENT_ROOT');
        if ($this->conf['customTempOutputPath']) {
            $this->outputPath .= Utility::sanitizePath($this->conf['customTempOutputPath']);
        } else {
            $this->outputPath .= '/typo3temp/tx_webkitpdf/';
        }

        $this->paramName = 'urls';
        if ($this->conf['customParameterName']) {
            $this->paramName = $this->conf['customParameterName'];
        }

        $this->filename = $this->outputPath . $this->conf['filePrefix'] . Utility::generateHash() . '.pdf';
        $this->filenameOnly = basename($this->filename);
        if ($this->conf['staticFileName']) {
            $this->filenameOnly = $this->conf['staticFileName'];
        }

        if (substr($this->filenameOnly, strlen($this->filenameOnly) - 4) !== '.pdf') {
            $this->filenameOnly .= '.pdf';
        }

        $this->readScriptSettings();
        $this->cacheManager = \tx_rnbase::makeInstance('DMK\\Webkitpdf\\Cache', $this->conf);

        $this->contentDisposition = 'attachment';
        if (intval($this->conf['openFilesInline']) === 1) {
            $this->contentDisposition = 'inline';
        }

        $this->initDosAttackPrevention();
    }

    /**
     * Wenn wir unbegrenzt viele URLs zu lassen, dann besteht die Gefahr
     * dass der Server auf sich selbst eine DoS Attacke ausführt, indem eine
     * große Anzahl von URLs übergeben wird.
     *
     * @return void
     */
    protected function initDosAttackPrevention()
    {
        if ($this->conf['numberOfUrlsAllowedToProcess']) {
            $this->makeSureNotMoreUrlsAreProcessedThanAllowed();
        }
    }

    /**
     * @return void
     */
    protected function makeSureNotMoreUrlsAreProcessedThanAllowed()
    {
        if (is_array($this->piVars[$this->paramName])) {
            $this->piVars[$this->paramName] = array_slice(
                $this->piVars[$this->paramName],
                0,
                intval($this->conf['numberOfUrlsAllowedToProcess'])
            );
        }
    }

    /**
     * The main method of the PlugIn
     *
     * @param   string      $content: The PlugIn content
     * @param   array       $conf: The PlugIn configuration
     * @return  The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        $urls = $this->getUrls();

        if (!empty($urls) && count($urls) > 0) {
            $urls = $this->sanitizeUrls($urls);

            $this->initializeFileNameToOfferAsDownload($urls);

            if ($this->conf['fileOnly'] == 1) {
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

    /**
     * Either get PDF filename from cache or generate the PDF
     *
     * @param array $urls
     *
     * @return void
     */
    protected function initializeFileNameToOfferAsDownload(array $urls)
    {
        $originalUrls = implode(' ', $urls);
        // Do not cache access restricted pages
        $loadFromCache = $GLOBALS['TSFE']->loginUser ? false : true;

        if (!$loadFromCache
            || !$this->cacheManager->isInCache($originalUrls)
            || $this->conf['debugScriptCall'] === '1'
        ) {
            $this->generatePdf($urls, $originalUrls);
        } else {
            $this->filename = $this->cacheManager->get($originalUrls);
        }
    }

    /**
     * @return array
     */
    protected function getUrls()
    {
        $urls = $this->piVars[$this->paramName];
        if (!$urls) {
            if (isset($this->conf['urls.'])) {
                $urls = $this->conf['urls.'];
            } else {
                $urls = array($this->conf['urls']);
            }
        }

        return $urls;
    }

    /**
     * @param array $urls
     *
     * @return array
     */
    protected function sanitizeUrls(array $urls)
    {
        $allowedHosts = false;
        if ($this->conf['allowedHosts']) {
            $allowedHosts = \Tx_Rnbase_Utility_Strings::trimExplode(',', $this->conf['allowedHosts']);
        }

        $utility = $this->getUtility();

        foreach ($urls as &$url) {
            if ($GLOBALS['TSFE']->loginUser) {
                $url = $utility->appendFESessionInfoToURL($url);
            }
            $url = $utility->sanitizeURL($url, $allowedHosts);
        }

        return $urls;
    }

    /**
     * @return Utility
     */
    protected function getUtility()
    {
        return new Utility();
    }

    /**
     * @param string $urls
     * @param string $origUrls
     *
     * @return void
     */
    protected function generatePdf($urls, $origUrls)
    {
        $this->scriptCall =
            escapeshellcmd($this->scriptPath . 'wkhtmltopdf') . ' ' .
            $this->buildScriptOptions() . ' ' .
            implode(' ', $urls) . ' ' .
            escapeshellarg($this->filename) .
            ' 2>&1';

        $this->callExec();

        // Write debugging information to devLog
        Utility::debugLogging('Executed shell command', -1, array($this->scriptCall));
        Utility::debugLogging('Output of shell command', -1, $this->scriptCallOutput);

        if ($this->pdfExists()) {
            $this->cacheManager->store($origUrls, $this->filename);
        }
    }

    /**
     * @return void
     */
    protected function callExec()
    {
        exec($this->scriptCall, $this->scriptCallOutput);
    }

    /**
     * @return bool
     */
    protected function pdfExists()
    {
        return  file_exists($this->filename) &&
                filesize($this->filename);
    }

    /**
     * @return void
     *
     * @todo write unit tests
     */
    protected function handlePdfExistsNot()
    {
        \tx_rnbase_util_Logger::warn(
            'PDF was not created successfully',
            'webkitpdf',
            array(
                'Executed shell command' => $this->scriptCall,
                'Output of shell command' => $this->scriptCallOutput
            )
        );

        \tx_rnbase_util_TYPO3::getTSFE()->pageNotFoundAndExit(
            'webkitpdf could not create the PDF file for the desired page. Check the devlog for more information.'
        );
    }

    /**
     * @return void
     *
     * @todo write unit tests
     */
    protected function offerPdfForDownload()
    {
        header('Content-type: application/pdf');
        header('Content-Transfer-Encoding: Binary');
        header('Content-Length: ' . filesize($this->filename));
        header('Content-Disposition: ' . $this->contentDisposition . '; filename="' . $this->filenameOnly . '"');
        header('X-Robots-Tag: noindex');
        readfile($this->filename);

        if (!$this->cacheManager->isCachingEnabled()) {
            unlink($this->filename);
        }

        exit(0);
    }

    protected function readScriptSettings()
    {
        $defaultSettings = array(
            'footer-right' => '[page]/[toPage]',
            'footer-font-size' => '6',
            'header-font-size' => '6',
            'margin-left' => '15mm',
            'margin-right' => '15mm',
            'margin-top' => '15mm',
            'margin-bottom' => '15mm',
        );

        $scriptParams = array();
        $tsSettings = $this->conf['scriptParams'];
        foreach ($defaultSettings as $param => $value) {
            if (!isset($tsSettings[$param])) {
                $tsSettings[$param] = $value;
            }
        }

        $finalSettings = array();
        foreach ($tsSettings as $param => $value) {
            $value = trim($value);
            if (substr($param, 0, 2) !== '--') {
                $param = '--' . $param;
            }
            $finalSettings[$param] = $value;
        }

        return $finalSettings;
    }

    /**
     * Creates the parameters for the wkhtmltopdf call.
     *
     * @return string The parameter string
     */
    protected function buildScriptOptions()
    {
        $options = array();
        if ($this->conf['pageURLInHeader']) {
            $options['--header-center'] = '[webpage]';
        }

        if ($this->conf['copyrightNotice']) {
            $options['--footer-left'] = '© ' . date('Y', time()) . $this->conf['copyrightNotice'] . '';
        }

        if ($this->conf['additionalStylesheet']) {
            $this->conf['additionalStylesheet'] = $this->sanitizePath($this->conf['additionalStylesheet'], false);
            $options['--user-style-sheet'] = \tx_rnbase_util_Misc::getIndpEnv('TYPO3_REQUEST_HOST') . $this->conf['additionalStylesheet'];
        }

        $userSettings = $this->readScriptSettings();
        $options = array_merge($options, $userSettings);

        $paramsString = '';
        foreach ($options as $param => $value) {
            if (strlen($value) > 0) {
                $value = escapeshellarg($value);
            }
            $paramsString .= ' ' . $param . ' ' . $value;
        }

        foreach ($_COOKIE as $cookieName => $cookieValue) {
            $paramsString .= ' --cookie ' . escapeshellarg($cookieName) . ' ' . escapeshellarg($cookieValue);
        }

        return $paramsString;
    }

    /**
     * Makes sure that given path has a slash as first and last character
     *
     * @param   string      $path: The path to be sanitized
     * @return  string      sanitized path
     */
    protected function sanitizePath($path, $trailingSlash = true)
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
     * Processes the stdWrap properties of the input array
     *
     * @param   array   The TypoScript array
     * @return  array   The processed values
     */
    protected function processStdWraps($tsSettings)
    {

        // Get TS values and process stdWrap properties
        if (is_array($tsSettings)) {
            foreach ($tsSettings as $key => $value) {
                $process = true;
                if (substr($key, -1) === '.') {
                    $key = substr($key, 0, -1);
                    if (array_key_exists($key, $tsSettings)) {
                        $process = false;
                    }
                }

                if ((substr($key, -1) === '.' && !array_key_exists(substr($key, 0, -1), $tsSettings)) ||
                    (substr($key, -1) !== '.' && array_key_exists($key . '.', $tsSettings)) && !strstr($key, 'scriptParams')) {
                    $tsSettings[$key] = $this->cObj->stdWrap($value, $tsSettings[$key . '.']);

                    // Remove the additional TS properties after processing, otherwise they'll be translated to pdf properties
                    if (isset($tsSettings[$key . '.'])) {
                        unset($tsSettings[$key . '.']);
                    }
                }
            }
        }

        return $tsSettings;
    }
}
