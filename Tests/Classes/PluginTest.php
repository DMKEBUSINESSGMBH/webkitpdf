<?php
namespace DMK\Webkitpdf;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Hannes Bochmann (hannes.bochmann@dmk-ebusiness.de)
*  All rights reserved
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
 * DMK\Webkitpdf$PluginTest
 *
 * @package         TYPO3
 * @subpackage      webkitpdf
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class PluginTest extends \tx_rnbase_tests_BaseTestCase {

    /**
     * @var string
     */
    protected $filename = PATH_site . 'typo3temp/var/Cache/.myTest.txt';

    /**
     * @var string
     */
    protected $scriptCall;

    /**
     * @var string
     */
    protected $scriptCallOutput;

    /**
     * @var string
     */
    private $userEmail = 'tegutcrm@dmkdev.de';

    protected function setUp()
    {
        if (!file_exists($this->filename) === true) {
            $content = "some text here";
            $fp = fopen($this->filename,"wb");
            fwrite($fp,$content);
            fclose($fp);
        }
    }

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown() {
        if (file_exists($this->filename) === true) {
            unlink($this->filename);
        }

        if (isset($_COOKIE['test1'])) {
            unset($_COOKIE['test1']);
        }
        if (isset($_COOKIE['test2'])) {
            unset($_COOKIE['test2']);
        }
    }

    /**
     * @group unit
     */
    public function testInitCallsInitDosAttackPrevention() {
        $plugin = $this->getMock('DMK\\Webkitpdf\\Plugin', array('initDosAttackPrevention'));
        $plugin->expects(self::once())
            ->method('initDosAttackPrevention');

        $this->callInaccessibleMethod($plugin, 'init', array());
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfNotConfigured() {
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('paramName', 'urls');
        $plugin->conf = array('');
        $plugin->piVars = array(
            'urls' => array(
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl'
            )
        );

        $this->callInaccessibleMethod($plugin, 'initDosAttackPrevention');

        self::assertEquals(
            array('urls' => array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl')),
            $plugin->piVars
        );
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfConfigured() {
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('paramName', 'urls');
        $plugin->conf = array('numberOfUrlsAllowedToProcess' => 3);
        $plugin->piVars = array(
            'urls' => array(
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl'
            )
        );

        $this->callInaccessibleMethod($plugin, 'initDosAttackPrevention');

        self::assertEquals(array('urls' => array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl')), $plugin->piVars);
    }

    /**
     * @group unit
     */
    public function testBuildScriptOptionsAddsCookies() {
        $_COOKIE['test1'] = 'value1';
        $_COOKIE['test2'] = 'value2';

        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $this->callInaccessibleMethod($plugin, 'buildScriptOptions');

        self::assertContains(
            ' --cookie \'test1\' \'value1\' --cookie \'test2\' \'value2\'',
            $this->callInaccessibleMethod($plugin, 'buildScriptOptions')
        );
    }

    /**
     * @group unit
     */
    public function testMainWithoutUrls(){
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array('getUrls','sanitizeUrls','generatePdf','pdfExists','offerPdfForDownload','handlePdfExistsNot')
        );

        $plugin->expects($this->once())
            ->method('getUrls');
        $plugin->expects($this->never())
            ->method('sanitizeUrls');

        $result = $this->callInaccessibleMethod($plugin,'pi_wrapInBaseClass', '');

        self::assertEquals(
            $result,
            $this->callInaccessibleMethod($plugin, 'main', 'Test', array()),
            "Main hat kein Ergebnis zurückgegeben."
        );
    }

    /**
     * @group unit
     */
    public function testMainWithUrls(){
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array('sanitizeUrls','generatePdf','pdfExists','offerPdfForDownload','handlePdfExistsNot')
        );
        $plugin->_set('paramName', 'urls');
        $plugin->conf['allowedHosts'] = 'wuppertal.localhost';
        $plugin->piVars = array(
            'urls' => array(0 => 'http://wuppertal.localhost/wsw-energie-wasser/privatkunden/', 1 => 'http://wuppertal.localhost/wsw-mobil/')
        );

        $plugin->expects($this->once())
            ->method('sanitizeUrls');

        $result = $this->callInaccessibleMethod($plugin,'pi_wrapInBaseClass', '');

        self::assertEquals(
            $result,
            $this->callInaccessibleMethod($plugin, 'main', 'Test', array()),
            "Main hat kein Ergebnis zurückgegeben."
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithPiVars(){
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('paramName', 'urls');
        $plugin->piVars = array(
            'urls' => array(
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl'
            )
        );

        self::assertEquals(
            array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl'),
            $this->callInaccessibleMethod($plugin, 'getUrls'),
            "Keine URLs in piVars gefunden"
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithConfUrlsPunkt(){
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->conf = array(
            'urls.' => 'firstUrl,secondUrl,thirdUrl,fourthUrl,fifthUrl'
        );

        self::assertEquals(
            'firstUrl,secondUrl,thirdUrl,fourthUrl,fifthUrl',
            $this->callInaccessibleMethod($plugin, 'getUrls'),
            "Keine URLs in conf urls gefunden"
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithConfUrls(){
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->conf = array(
            'urls' => array(
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl'
            )
        );

        self::assertEquals(
            array(array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl')),
            $this->callInaccessibleMethod($plugin, 'getUrls'),
            "Keine URLs in conf urls gefunden"
        );
    }

    /**
     * @group unit
     */
    public function testSanitizeUrlsWithAllowedHosts(){
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array('appendFESessionInfoToURL','sanitizeURL')
        );
        $plugin->conf['allowedHosts'] = 'wuppertal.localhost';
        $urls = array(0 => 'http://wuppertal.localhost/wsw-energie-wasser/privatkunden/', 1 => 'http://wuppertal.localhost/wsw-mobil/');

        self::assertEquals(
            array(0 => "'http://wuppertal.localhost/wsw-energie-wasser/privatkunden/'", 1 => "'http://wuppertal.localhost/wsw-mobil/'"),
            $this->callInaccessibleMethod($plugin,'sanitizeUrls', $urls),
            "URLS could not be sanitized"
        );
    }

    /**
     * @group unit
     */
    public function testSanitizeUrlsWithoutAllowedHosts(){
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array('appendFESessionInfoToURL','sanitizeURL')
        );
        $exceptionMsg = 'Host "wuppertal.localhost" does not match TYPO3 host.';
        $urls = array(0 => 'http://wuppertal.localhost/wsw-energie-wasser/privatkunden/', 1 => 'http://wuppertal.localhost/wsw-mobil/');

        self::assertEquals(
            $this->setExpectedException(\Exception::class, $exceptionMsg),
            $this->callInaccessibleMethod($plugin,'sanitizeUrls', $urls),
            "URLS could not be sanitized"
        );
    }

    /**
     * @group unit
     */
    public function testAppendFESessionInfoToURLWithUrl(){
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array('appendFESessionInfoToURL','sanitizeURL')
            );

        $utility = $this->callInaccessibleMethod($plugin,'getUtility');

        self::assertEquals(
            "http://wuppertal.localhost/wsw-mobil/?FE_SESSION_KEY=-ea167a63cebc16788993ee16a410d793",
            $this->callInaccessibleMethod($utility,'appendFESessionInfoToURL', 'http://wuppertal.localhost/wsw-mobil/'),
            "Information about the FE User could not be appended to URL"
        );
    }

    /**
     * @group unit
     */
    public function testAppendFESessionInfoToURLWithEmptyUrl(){
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array('appendFESessionInfoToURL','sanitizeURL')
        );
        $utility = $this->callInaccessibleMethod($plugin,'getUtility');

        self::assertEquals(
            "?FE_SESSION_KEY=-ea167a63cebc16788993ee16a410d793",
            $this->callInaccessibleMethod($utility,'appendFESessionInfoToURL', ''),
            "Information about the FE User could not be appended to URL"
        );
    }

    /**
     * @group unit
     */
    public function testExecute(){
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array('buildScriptOptions')
        );
        $plugin->_set('scriptCall', 'echo "DMK PDF test"');

        $urls = array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl');

        $this->callInaccessibleMethod($plugin, 'callExec');
        self::assertEquals(array('DMK PDF test'), $plugin->_get('scriptCallOutput'));
    }

    /**
     * @group unit
     */
    public function testPdfExists(){
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('filename', $this->filename);
        self::assertTrue($this->callInaccessibleMethod($plugin, 'pdfExists'),'Datei nicht vorhanden');
    }

}

