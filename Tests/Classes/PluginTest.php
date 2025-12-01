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

namespace DMK\Webkitpdf\Tests;

use DMK\Webkitpdf\Cache;
use DMK\Webkitpdf\Plugin;
use DMK\Webkitpdf\Utility;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
     * @var ServerRequestInterface
     */
    protected \PHPUnit\Framework\MockObject\MockObject $request;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['webkitpdf']['debug'] = false;
        $this->filename = Environment::getPublicPath().$this->filename;
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    /**
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        if (file_exists($this->filename)) {
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
    public function testInitCallsInitDosAttackPrevention(): void
    {
        $plugin = $this->getAccessibleMock(
            Plugin::class,
            ['initDosAttackPrevention', 'processStdWraps'],
            [],
            '',
            false
        );
        $plugin->expects(self::once())
            ->method('processStdWraps')
            ->willReturn(['customScriptPath' => '']);
        $plugin->expects(self::once())
            ->method('initDosAttackPrevention');

        $plugin->_call('init', [], $this->request);
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfNotConfigured(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set(
            'requestParameters',
            [
                'urls' => [
                    0 => 'firstUrl',
                    1 => 'secondUrl',
                    2 => 'thirdUrl',
                    3 => 'fourthUrl',
                    4 => 'fifthUrl',
                ],
            ]
        );
        $plugin->_set('conf', []);

        $plugin->_call('initDosAttackPrevention');

        self::assertEquals(
            ['urls' => [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl', 3 => 'fourthUrl', 4 => 'fifthUrl']],
            $plugin->_get('requestParameters')
        );
    }

    /**
     * @group unit
     */
    public function testInitDosAttackPreventionIfConfigured(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set('requestParameterName', 'urls');
        $plugin->_set(
            'requestParameters',
            [
                'urls' => [
                    0 => 'firstUrl',
                    1 => 'secondUrl',
                    2 => 'thirdUrl',
                    3 => 'fourthUrl',
                    4 => 'fifthUrl',
                ],
            ]
        );
        $plugin->_set('conf', ['numberOfUrlsAllowedToProcess' => 3]);

        $plugin->_call('initDosAttackPrevention');

        self::assertEquals(
            ['urls' => [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl']],
            $plugin->_get('requestParameters')
        );
    }

    /**
     * @group unit
     */
    public function testBuildScriptOptionsAddsCookies(): void
    {
        $_COOKIE['test1'] = 'value1';
        $_COOKIE['test2'] = 'value2';

        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_call('buildScriptOptions');

        self::assertStringContainsString(
            " --cookie 'test1' 'value1' --cookie 'test2' 'value2'",
            $plugin->_call('buildScriptOptions')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsPrefersRequestParametersOverTypoScriptConfiguration(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set('requestParameterName', 'urls');
        $plugin->_set('requestParameters', ['urls' => [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl']]);
        $plugin->_set('conf', ['urls.' => ['fourthUrl', 'fifthUrl']]);

        self::assertEquals(
            [0 => 'firstUrl', 1 => 'secondUrl', 2 => 'thirdUrl'],
            $plugin->_call('getUrls')
        );
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsArray(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set('conf', ['urls.' => ['firstUrl', 'secondUrl', 'thirdUrl']]);

        self::assertEquals(['firstUrl', 'secondUrl', 'thirdUrl'], $plugin->_call('getUrls'));
    }

    /**
     * @group unit
     */
    public function testGetUrlsWithUrlsFromTypoScriptWhenConfigurationIsString(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set('conf', ['urls' => 'firstUrl']);

        self::assertEquals(['firstUrl'], $plugin->_call('getUrls'));
    }

    /**
     * @param array || boolean $expectedAllowedHostsForUtilityMethod
     *
     * @group unit
     *
     * @dataProvider dataProviderSanitizeUrls
     */
    #[DataProvider('dataProviderSanitizeUrls')]
    public function testSanitizeUrlsWithoutFrontendUser(
        string $allowedHostsConfiguration,
        array $expectedAllowedHostsForUtilityMethod,
    ): void {
        $utility = $this->getMockBuilder(Utility::class)
            ->onlyMethods(['sanitizeUrl'])
            ->getMock();

        $matcher = self::exactly(2);
        $utility->expects($matcher)
            ->method('sanitizeUrl')
            ->with(
                $this->callback(function (string $url) use ($matcher): bool {
                    self::assertSame(
                        match ($matcher->numberOfInvocations()) {
                            1 => 'firstUrl',
                            2 => 'secondUrl',
                        },
                        $url
                    );

                    return true;
                }),
                $expectedAllowedHostsForUtilityMethod
            )
            ->willReturnOnConsecutiveCalls(
                'firstUrlSanitized',
                'secondUrlSanitized'
            );

        $plugin = $this->getAccessibleMock(Plugin::class, ['getUtility'], [], '', false);

        $plugin->expects(self::once())
            ->method('getUtility')
           ->willReturn($utility);
        $plugin->_set('conf', ['allowedHosts' => $allowedHostsConfiguration]);

        self::assertEquals(
            ['firstUrlSanitized', 'secondUrlSanitized'],
            $plugin->_call('sanitizeUrls', ['firstUrl', 'secondUrl'])
        );
    }

    /**
     * @return string[][]|bool[][]|string[][][]
     */
    public static function dataProviderSanitizeUrls(): array
    {
        return [
            ['example.com, example.org', ['example.com', 'example.org']],
            ['', []],
        ];
    }

    /**
     * @group unit
     */
    public function testGeneratePdfIfNotCreatedSuccessfully(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions', 'pdfExists', 'callExec'], [], '', false);

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
           ->willReturn('--someArgs test');

        $plugin->expects(self::once())
            ->method('pdfExists')
           ->willReturn(false);

        $cacheManager = $this->getMockBuilder(Cache::class)
            ->onlyMethods(['store'])
            ->getMock();
        $cacheManager->expects(self::never())
            ->method('store');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $plugin->_call('generatePdf', ['first', 'second'], 'first, second');

        self::assertEquals(
            "/some/path/wkhtmltopdf --someArgs test first second '/some/otherpath/file.pdf' 2>&1",
            $plugin->_get('scriptCall')
        );
    }

    /**
     * @group unit
     */
    public function testGeneratePdfIfCreatedSuccessfully(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions', 'pdfExists', 'callExec'], [], '', false);

        $plugin->expects(self::once())
            ->method('callExec');

        $plugin->expects(self::once())
            ->method('buildScriptOptions')
           ->willReturn('--someArgs test');

        $plugin->expects(self::once())
            ->method('pdfExists')
           ->willReturn(true);

        $cacheManager = $this->getMockBuilder(Cache::class)
            ->onlyMethods(['store'])
            ->getMock();
        $cacheManager->expects(self::once())
            ->method('store')
            ->with('first, second', '/some/otherpath/file.pdf');
        $plugin->_set('cacheManager', $cacheManager);

        $plugin->_set('scriptPath', '/some/path/');
        $plugin->_set('filename', '/some/otherpath/file.pdf');

        $plugin->_call('generatePdf', ['first', 'second'], 'first, second');

        self::assertEquals(
            "/some/path/wkhtmltopdf --someArgs test first second '/some/otherpath/file.pdf' 2>&1",
            $plugin->_get('scriptCall')
        );
    }

    /**
     * @group unit
     */
    public function testCallExec(): void
    {
        $plugin = $this->getAccessibleMock(Plugin::class, ['buildScriptOptions'], [], '', false);
        $plugin->_set('scriptCall', 'echo "DMK PDF test"');

        $plugin->_call('callExec');
        self::assertEquals(['DMK PDF test'], $plugin->_get('scriptCallOutput'));
    }

    /**
     * @group unit
     */
    public function testPdfExists(): void
    {
        file_put_contents($this->filename, 'test');
        $plugin = $this->getAccessibleMock(Plugin::class, ['main'], [], '', false);
        $plugin->_set('filename', $this->filename);
        self::assertTrue($plugin->_call('pdfExists'), 'Datei nicht vorhanden');
    }

    /**
     * @group unit
     */
    public function testMainWhenNoUrlsGiven(): void
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
           ->willReturn([]);
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

        self::assertSame('', $plugin->_call('main', 'Test', ['someConfiguration'], $this->request));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndFileOnlyConfigured(): void
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
           ->willReturn([
               0 => 'first',
               1 => 'second',
           ]);
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
           ->willReturn([
               0 => 'firstSanitized',
               1 => 'secondSanitized',
           ]);
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

        self::assertSame('fileOnly', $plugin->_call('main', 'Test', ['someConfiguration'], $this->request));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfNotCreated(): void
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
           ->willReturn([
               0 => 'first',
               1 => 'second',
           ]);
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
           ->willReturn([
               0 => 'firstSanitized',
               1 => 'secondSanitized',
           ]);
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]);
        $plugin->expects(self::once())
            ->method('pdfExists')
           ->willReturn(false);
        $plugin->expects(self::never())
            ->method('offerPdfForDownload');
        $plugin->expects(self::once())
            ->method('handlePdfExistsNot');

        self::assertSame('', $plugin->_call('main', 'Test', ['someConfiguration'], $this->request));
    }

    /**
     * @group unit
     */
    public function testMainWhenUrlsGivenAndPdfCreated(): void
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
            ->willReturn([
                0 => 'first',
                1 => 'second',
            ]);
        $plugin->expects(self::once())
            ->method('sanitizeUrls')
            ->with([
                0 => 'first',
                1 => 'second',
            ])
           ->willReturn([
               0 => 'firstSanitized',
               1 => 'secondSanitized',
           ]);
        $plugin->expects(self::once())
            ->method('initializeFileNameToOfferAsDownload')
            ->with([
                0 => 'firstSanitized',
                1 => 'secondSanitized',
            ]);
        $plugin->expects(self::once())
            ->method('pdfExists')
           ->willReturn(true);
        $plugin->expects(self::once())
            ->method('offerPdfForDownload');
        $plugin->expects(self::once())
            ->method('handlePdfExistsNot');

        self::assertSame('', $plugin->_call('main', 'Test', ['someConfiguration'], $this->request));
    }
}
