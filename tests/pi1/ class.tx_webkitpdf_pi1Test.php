<?php
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
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');
tx_rnbase::load('tx_webkitpdf_pi1');

/**
 * tx_webkitpdf_pi1Test
 *
 * @package 		TYPO3
 * @subpackage	 	webkitpdf
 * @author 			Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_webkitpdf_pi1Test extends tx_rnbase_tests_BaseTestCase {

	/**
	 * @group unit
	 */
	public function testInitCallsInitDosAttackPrevention() {
		$plugin = $this->getMock('tx_webkitpdf_pi1', array('initDosAttackPrevention'));
		$plugin->expects(self::once())
			->method('initDosAttackPrevention');

		$this->callInaccessibleMethod($plugin, 'init', array());
	}

	/**
	 * @group unit
	 */
	public function testInitDosAttackPreventionIfNotConfigured() {
		$plugin = tx_rnbase::makeInstance($this->buildAccessibleProxy('tx_webkitpdf_pi1'));
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
		$plugin = tx_rnbase::makeInstance($this->buildAccessibleProxy('tx_webkitpdf_pi1'));
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
}

