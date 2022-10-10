<?php

namespace DMK\Webkitpdf\Tests;

use DMK\Webkitpdf\Plugin;
use DMK\Webkitpdf\Utility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Core\Environment;

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
 * DMK\Webkitpdf$PluginTest.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class PluginTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $filename = '/typo3temp/.webkitpdf.test';

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
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['debug'] = false;
        $this->filename = Environment::getPublicPath().$this->filename;
    }

    /**
     * {@inheritDoc}
     *
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        if (true === file_exists($this->filename)) {
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
    public function testInitCallsInitDosAttackPrevention()
    {
        $plugin = $this->getMockBuilder(Plugin::class)
            ->setMethods(['initDosAttackPrevention'])
            ->disableOriginalConstructor()
            ->getMock();
        $plugin->expects(self::once())
            ->method('initDosAttackPrevention');

        $this->callInaccessibleMethod($plugin, 'init', []);
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfNotConfigured()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->_set('paramName', 'urls');
        $plugin->conf = [''];
        $plugin->piVars = [
            'urls' => [
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl',
            ],
        ];

        $this->callInaccessibleMethod($plugin, 'initDosAttackPrevention');

        self::assertEquals(
            ['urls' => [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl']],
            $plugin->piVars
        );
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfConfigured()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->_set('paramName', 'urls');
        $plugin->conf = ['numberOfUrlsAllowedToProcess' => 3];
        $plugin->piVars = [
            'urls' => [
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl',
            ],
        ];

        $this->callInaccessibleMethod($plugin, 'initDosAttackPrevention');

        self::assertEquals(['urls' => [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl']], $plugin->piVars);
    }

    /**
     * @group unit
     */
    public function testBuildScriptOptionsAddsCookies()
    {
        $_COOKIE['test1'] = 'value1';
        $_COOKIE['test2'] = 'value2';

        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $this->callInaccessibleMethod($plugin, 'buildScriptOptions');

        self::assertStringContainsString(
            ' --cookie \'test1\' \'value1\' --cookie \'test2\' \'value2\'',
            $this->callInaccessibleMethod($plugin, 'buildScriptOptions')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsPrefersPiVarsOverTypoScriptConfiguration()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->_set('paramName', 'urls');
        $plugin->piVars = [
            'urls' => [
                0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl',
            ],
        ];
        $plugin->conf = [
            'urls.' => ['fourthUrl', 'fifthUrl'],
        ];

        self::assertEquals(
            [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl'],
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsArray()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->conf = [
            'urls.' => ['firstUrl', 'secondUrl', 'thirdUrl'],
        ];

        self::assertEquals(
            ['firstUrl', 'secondUrl', 'thirdUrl'],
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsString()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->conf = ['urls' => 'firstUrl'];

        self::assertEquals(
            ['firstUrl'],
            $this->callInaccessibleMethod($plugin, 'getUrls')
        );
    }

    /**
     * @param string $allowedHostsConfiguration
     * @param array || boolean $expectedAllowedHostsForUtilityMethod
     *
     * @group unit
     *
     * @dataProvider dataProviderSanitizeUrls
     */
    public function testSanitizeUrlsWithoutFrontendUser(
        $allowedHostsConfiguration,
        $expectedAllowedHostsForUtilityMethod
    ) {
        $utility = $this->getMockBuilder(Utility::class)
            ->setMethods(['sanitizeUrl'])
            ->getMock();

        $utility->expects(self::exactly(2))
            ->method('sanitizeUrl')
            ->withConsecutive(
                ['firstUrl', $expectedAllowedHostsForUtilityMethod],
                ['secondUrl', $expectedAllowedHostsForUtilityMethod],
            )
            ->willReturnOnConsecutiveCalls(
                self::returnValue('firstUrlSanitized'),
                self::returnValue('secondUrlSanitized')
            );

        $plugin = $this->getMockBuilder(Plugin::class)
            ->setMethods(['getUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $plugin->expects(self::once())
            ->method('getUtility')
            ->will(self::returnValue($utility));
        $plugin->conf['allowedHosts'] = $allowedHostsConfiguration;

        self::assertEquals(
            ['firstUrlSanitized', 'secondUrlSanitized'],
            $this->callInaccessibleMethod($plugin, 'sanitizeUrls', ['firstUrl', 'secondUrl'])
        );
    }

    /**
     * @return string[][]|bool[][]|string[][][]
     */
    public function dataProviderSanitizeUrls()
    {
        return [
            ['example.com, example.org', ['example.com', 'example.org']],
            ['', []],
        ];
    }

    /**
     * @group unit
     */
    public function testGeneratePdfIfNotCreatedSuccessfully()
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions', 'pdfExists', 'callExec'], [], '', false);

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
            ->will(self::returnValue('--someArgs test'));

        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(false));

        $cacheManager = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['store'])
            ->getMock();
        $cacheManager->expects(self::never())
            ->method('store');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $this->callInaccessibleMethod($plugin, 'generatePdf', ['first', 'second'], 'first, second');

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
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions', 'pdfExists', 'callExec'], [], '', false);

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
            ->will(self::returnValue('--someArgs test'));

        $plugin->expects(self::once())
            ->method('pdfExists')
            ->will(self::returnValue(true));

        $cacheManager = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['store'])
            ->getMock();
        $cacheManager->expects(self::once())
            ->method('store')
            ->with('first, second', '/some/otherpath/file.pdf');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $this->callInaccessibleMethod($plugin, 'generatePdf', ['first', 'second'], 'first, second');

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
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions'], [], '', false);
        $plugin->_set('scriptCall', 'echo "DMK PDF test"');

        $this->callInaccessibleMethod($plugin, 'callExec');
        self::assertEquals(['DMK PDF test'], $plugin->_get('scriptCallOutput'));
    }

    /**
     * @group unit
     */
    public function testPdfExists()
    {
        file_put_contents($this->filename, 'test');
        $plugin = $this->getAccessibleMock(Plugin::class, ['dummy'], [], '', false);
        $plugin->_set('filename', $this->filename);
        self::assertTrue($plugin->_call('pdfExists'), 'Datei nicht vorhanden');
    }

    /**
     * @group unit
     */
    public function testMainWhenNoUrlsGiven()
    {
        $plugin = $this->getAccessibleMock(
            Plugin::class,
            [
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            ],
            [],
            '',
            false
        );

        $plugin->expects(self::once())
            ->method('init')
            ->with(['someConfiguration']);
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue([]));
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

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', ['someConfiguration']));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndFileOnlyConfigured()
    {
        $plugin = $this->getAccessibleMock(
            Plugin::class,
            [
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            ],
            [],
            '',
            false
        );

        $plugin->_set('conf', ['fileOnly' => true]);
        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(['someConfiguration']);
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue([
                0 => 'first',
                1 => 'second',
            ]));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
            ->will(self::returnValue([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]);
        $plugin->expects(self::never())
            ->method('pdfExists');
        $plugin->expects(self::never())
            ->method('offerPdfForDownload');
        $plugin->expects(self::never())
            ->method('handlePdfExistsNot');
        $plugin->expects(self::never())
            ->method('pi_wrapInBaseClass');

        self::assertSame('fileOnly', $this->callInaccessibleMethod($plugin, 'main', 'Test', ['someConfiguration']));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfNotCreated()
    {
        $plugin = $this->getAccessibleMock(
            Plugin::class,
            [
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            ],
            [],
            '',
            false
        );

        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(['someConfiguration']);
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue([
                0 => 'first',
                1 => 'second',
            ]));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
            ->will(self::returnValue([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]);
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

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', ['someConfiguration']));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfCreated()
    {
        $plugin = $this->getAccessibleMock(
            Plugin::class,
            [
                'getUrls',
                'sanitizeUrls',
                'initializeFileNameToOfferAsDownload',
                'pdfExists',
                'offerPdfForDownload',
                'handlePdfExistsNot',
                'pi_wrapInBaseClass',
                'init',
            ],
            [],
            '',
            false
        );

        $plugin->_set('filename', 'fileOnly');

        $plugin->expects(self::once())
            ->method('init')
            ->with(['someConfiguration']);
        $plugin->expects(self::once())
            ->method('getUrls')
            ->will(self::returnValue([
                0 => 'first',
                1 => 'second',
            ]));
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
            ->will(self::returnValue([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]));
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]);
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

        self::assertSame('tested', $this->callInaccessibleMethod($plugin, 'main', 'Test', ['someConfiguration']));
    }
}
