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
class PluginTest extends \tx_rnbase_tests_BaseTestCase
{

    /**
     * @var string
     */
    protected $filename = PATH_site . 'typo3temp/.webkitpdf.test';

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

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['webkitpdf']['debug'] = false;
    }

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        if (file_exists($this->filename) === true) {
            unlink($this->filename);
        }

        if (isset($_COOKIE['test1'])) {
            unset($_COOKIE['test1']);
        }
        if (isset($_COOKIE['test2'])) {
            unset($_COOKIE['test2']);
        }

        if (isset($GLOBALS['TSFE']->loginUser)) {
            unset($GLOBALS['TSFE']->loginUser);
        }
    }

    /**
     * @group unit
     */
    public function testInitCallsInitDosAttackPrevention()
    {
        $plugin = $this->getMock('DMK\\Webkitpdf\\Plugin', array('initDosAttackPrevention'));
        $plugin->expects(self::once())
            ->method('initDosAttackPrevention');

        $this->callInaccessibleMethod($plugin, 'init', array());
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfNotConfigured()
    {
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
    public function testInitDosAttackPreventionIfConfigured()
    {
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
    public function testBuildScriptOptionsAddsCookies()
    {
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
    public function testGetUrlsPrefersPiVarsOverTypoScriptConfiguration()
    {
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('paramName', 'urls');
        $plugin->piVars = array(
            'urls' => array(
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl'
            )
        );
        $plugin->conf = array(
            'urls.' => array('fourthUrl', 'fifthUrl')
        );

        self::assertEquals(
            array(0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl'),
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsArray()
    {
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->conf = array(
            'urls.' => array('firstUrl', 'secondUrl', 'thirdUrl')
        );

        self::assertEquals(
            array('firstUrl', 'secondUrl', 'thirdUrl'),
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsString()
    {
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->conf = array('urls' => 'firstUrl');

        self::assertEquals(
            array('firstUrl'),
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @param string $allowedHostsConfiguration
     * @param array || boolean $expectedAllowedHostsForUtilityMethod
     *
     * @group unit
     * @dataProvider dataProviderSanitizeUrls
     */
    public function testSanitizeUrlsWithoutFrontendUser(
        $allowedHostsConfiguration,
        $expectedAllowedHostsForUtilityMethod
    ) {
        $utility = $this->getMock('DMK\\Webkitpdf\\Utility', array('appendFESessionInfoToURL','sanitizeURL'));

        $utility->expects(self::never())
            ->method('appendFESessionInfoToURL');

        $utility->expects(self::at(0))
            ->method('sanitizeURL')
            ->with('firstUrl', $expectedAllowedHostsForUtilityMethod)
            ->will(self::returnValue('firstUrlSanitized'));

        $utility->expects(self::at(1))
            ->method('sanitizeURL')
            ->with('secondUrl', $expectedAllowedHostsForUtilityMethod)
            ->will(self::returnValue('secondUrlSanitized'));

        $plugin = $this->getMock('DMK\\Webkitpdf\\Plugin', array('getUtility'));

        $plugin->expects(self::once())
            ->method('getUtility')
            ->will(self::returnValue($utility));
        $plugin->conf['allowedHosts'] = $allowedHostsConfiguration;

        self::assertEquals(
            array('firstUrlSanitized', 'secondUrlSanitized'),
            $this->callInaccessibleMethod($plugin, 'sanitizeUrls', array('firstUrl', 'secondUrl'))
        );
    }

    /**
     * @param string $allowedHostsConfiguration
     * @param array || boolean $expectedAllowedHostsForUtilityMethod
     *
     * @group unit
     * @dataProvider dataProviderSanitizeUrls
     */
    public function testSanitizeUrlsWithFrontendUser(
        $allowedHostsConfiguration,
        $expectedAllowedHostsForUtilityMethod
    ) {
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->loginUser = 'test';
        $utility = $this->getMock('DMK\\Webkitpdf\\Utility', array('appendFESessionInfoToURL','sanitizeURL'));

        $utility->expects(self::at(0))
            ->method('appendFESessionInfoToURL')
            ->with('firstUrl')
            ->will(self::returnValue('firstUrlForFeUser'));

        $utility->expects(self::at(1))
            ->method('sanitizeURL')
            ->with('firstUrlForFeUser', $expectedAllowedHostsForUtilityMethod)
            ->will(self::returnValue('firstUrlSanitized'));

        $utility->expects(self::at(2))
            ->method('appendFESessionInfoToURL')
            ->with('secondUrl')
            ->will(self::returnValue('secondUrlForFeUser'));

        $utility->expects(self::at(3))
            ->method('sanitizeURL')
            ->with('secondUrlForFeUser', $expectedAllowedHostsForUtilityMethod)
            ->will(self::returnValue('secondUrlSanitized'));

        $plugin = $this->getMock('DMK\\Webkitpdf\\Plugin', array('getUtility'));

        $plugin->expects(self::once())
            ->method('getUtility')
            ->will(self::returnValue($utility));
        $plugin->conf['allowedHosts'] = $allowedHostsConfiguration;

        self::assertEquals(
            array('firstUrlSanitized', 'secondUrlSanitized'),
            $this->callInaccessibleMethod($plugin, 'sanitizeUrls', array('firstUrl', 'secondUrl'))
        );
    }

    /**
     * @return string[][]|boolean[][]|string[][][]
     */
    public function dataProviderSanitizeUrls()
    {
        return array(
            array('example.com, example.org', array('example.com', 'example.org')),
            array('', false)
        );
    }

    /**
     * @group unit
     */
    public function testGetUtility()
    {
        self::assertInstanceOf(Utility::class, $this->callInaccessibleMethod(new Plugin(), 'getUtility'));
    }

    /**
     * @group unit
     */
    public function testGeneratePdfIfNotCreatedSuccessfully()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array('buildScriptOptions', 'pdfExists', 'callExec')
        );

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
            ->will(self::returnValue('--someArgs test'));

        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(false));

        $cacheManager = $this->getMock('stdClass', array('store'));
        $cacheManager->expects(self::never())
            ->method('store');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $this->callInaccessibleMethod($plugin, 'generatePdf', array('first', 'second'), 'first, second');

        self::assertEquals(
            '/some/path/wkhtmltopdf --someArgs test first second \'/some/otherpath/file.pdf\' 2>&1',
            $plugin->_get('scriptCall')
        );
    }

    /**
     * @group unit
     */
    public function testGeneratePdfIfCreatedSuccessfully()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array('buildScriptOptions', 'pdfExists', 'callExec')
        );

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
            ->will(self::returnValue('--someArgs test'));

        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(true));

        $cacheManager = $this->getMock('stdClass', array('store'));
        $cacheManager->expects(self::once())
            ->method('store')
            ->with('first, second', '/some/otherpath/file.pdf');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $this->callInaccessibleMethod($plugin, 'generatePdf', array('first', 'second'), 'first, second');

        self::assertEquals(
            '/some/path/wkhtmltopdf --someArgs test first second \'/some/otherpath/file.pdf\' 2>&1',
            $plugin->_get('scriptCall')
        );
    }

    /**
     * @group unit
     */
    public function testCallExec()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array('buildScriptOptions')
        );
        $plugin->_set('scriptCall', 'echo "DMK PDF test"');

        $this->callInaccessibleMethod($plugin, 'callExec');
        self::assertEquals(array('DMK PDF test'), $plugin->_get('scriptCallOutput'));
    }

    /**
     * @group unit
     */
    public function testPdfExists()
    {
        file_put_contents($this->filename, 'test');
        $plugin = \tx_rnbase::makeInstance($this->buildAccessibleProxy('DMK\\Webkitpdf\\Plugin'));
        $plugin->_set('filename', $this->filename);
        self::assertTrue($this->callInaccessibleMethod($plugin, 'pdfExists'), 'Datei nicht vorhanden');
    }

    /**
     * @group unit
     */
    public function testMainWhenNoUrlsGiven()
    {
        $plugin = $this->getMock(
            'DMK\\Webkitpdf\\Plugin',
            array(
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            )
        );

        $plugin->expects(self::once())
            ->method('init')
            ->with(array('someConfiguration'));
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue(array()));
        $plugin->expects(self::never())
            ->method('sanitizeUrls');
        $plugin->expects(self::never())
            ->method('initializeFileNameToOfferAsDownload');
        $plugin->expects(self::never())
            ->method('pdfExists');
        $plugin->expects(self::never())
            ->method('offerPdfForDownload');
        $plugin->expects(self::never())
            ->method('handlePdfExistsNot');
        $plugin->expects(self::once())
            ->method('pi_wrapInBaseClass')
            ->with('')
            ->will(self::returnValue('tested'));

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', array('someConfiguration')));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndFileOnlyConfigured()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array(
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            )
        );

        $plugin->_set('conf', array('fileOnly' => true));
        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(array('someConfiguration'));
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue(array(
                0 => 'first',
                1 => 'second'
            )));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with(array(
                0 => 'first',
                1 => 'second'
            ))
            ->will(self::returnValue(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            )));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            ));
        $plugin->expects(self::never())
            ->method('pdfExists');
        $plugin->expects(self::never())
            ->method('offerPdfForDownload');
        $plugin->expects(self::never())
            ->method('handlePdfExistsNot');
        $plugin->expects(self::never())
            ->method('pi_wrapInBaseClass');

        self::assertSame('fileOnly', $this->callInaccessibleMethod($plugin, 'main', 'Test', array('someConfiguration')));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfNotCreated()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array(
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            )
        );

        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(array('someConfiguration'));
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue(array(
                0 => 'first',
                1 => 'second'
            )));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with(array(
                0 => 'first',
                1 => 'second'
            ))
            ->will(self::returnValue(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            )));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            ));
        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(false));
        $plugin->expects(self::never())
            ->method('offerPdfForDownload');
        $plugin->expects(self::once())
            ->method('handlePdfExistsNot');
        $plugin->expects(self::once())
            ->method('pi_wrapInBaseClass')
            ->with('')
            ->will(self::returnValue('tested'));

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', array('someConfiguration')));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfCreated()
    {
        $plugin = $this->getAccessibleMock(
            'DMK\\Webkitpdf\\Plugin',
            array(
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            )
        );

        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(array('someConfiguration'));
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue(array(
                0 => 'first',
                1 => 'second'
            )));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with(array(
                0 => 'first',
                1 => 'second'
            ))
            ->will(self::returnValue(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            )));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with(array(
                0 => 'firstSanitized',
                1 => 'secondSanitized'
            ));
        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(true));
        $plugin->expects(self::once())
            ->method('offerPdfForDownload');
        $plugin->expects(self::never())
            ->method('handlePdfExistsNot');
        $plugin->expects(self::once())
            ->method('pi_wrapInBaseClass')
            ->with('')
            ->will(self::returnValue('tested'));

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', array('someConfiguration')));
    }
}
