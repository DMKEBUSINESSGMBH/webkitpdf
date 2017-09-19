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
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown() {
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
}

