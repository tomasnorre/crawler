<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Fixtures\Extensions\crawler_callback_test\Classes;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Queue\QueueCallbackInterface;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * A callback registered only by autoconfiguration, with a dependency injected by the container.
 */
final readonly class GreetingCallback implements QueueCallbackInterface
{
    public function __construct(
        private Typo3Version $typo3Version,
    ) {
    }

    public function execute(array $parameters): mixed
    {
        return 'Hello ' . $parameters['name'] . ' from TYPO3 ' . $this->typo3Version->getMajorVersion();
    }
}
