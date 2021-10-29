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

return [
    AOE\Crawler\Domain\Model\Configuration::class => [
        'tableName' => 'tx_crawler_configuration',
    ],
    AOE\Crawler\Domain\Model\Process::class => [
        'tableName' => 'tx_crawler_process',
    ],
    AOE\Crawler\Domain\Model\Queue::class => [
        'tableName' => 'tx_crawler_queue',
    ],
];
