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

$EM_CONF['webkitpdf'] = [
    'title' => 'Webkit PDFs',
    'description' => 'Generate PDF files using WebKit rendering engine.',
    'category' => 'plugin',
    'version' => '13.0.1',
    'state' => 'beta',
    'clearcacheonload' => 0,
    'author' => 'DMK E-BUSINESS GmbH',
    'author_email' => 'dev@dmk-ebusiness.com',
    'author_company' => 'DMK E-BUSINESS GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
