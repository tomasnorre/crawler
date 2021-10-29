<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

$EM_CONF['typo3_console'] = [
    'title' => 'TYPO3 Console',
    'description' => 'A reliable and powerful command line interface for TYPO3 CMS',
    'category' => 'cli',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => 'helhum.io',
    'version' => '5.7.2',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.3.99',
            'typo3' => '8.7.22-9.5.99',
            'extbase' => '8.7.22-9.5.99',
            'extensionmanager' => '8.7.22-9.5.99',
            'fluid' => '8.7.22-9.5.99',
            'install' => '8.7.22-9.5.99',
            'scheduler' => '8.7.22-9.5.99',
        ],
        'conflicts' => [
            'dbal' => '',
        ],
        'suggests' => [
        ],
    ],
];
