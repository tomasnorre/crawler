<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Queue;

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

use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Tests\Functional\Fixtures\Extensions\crawler_callback_test\Classes\GreetingCallback;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Callbacks of other extensions are registered by autoconfiguration and get their dependencies injected.
 */
#[CoversClass(CallbackExecutionStrategy::class)]
#[CoversClass(QueueExecutor::class)]
class QueueCallbackTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/crawler',
        __DIR__ . '/../Fixtures/Extensions/crawler_callback_test',
    ];

    #[Test]
    public function executesCallbackOfAnotherExtensionFromTheContainer(): void
    {
        $queueItem = [
            'parameters' => json_encode([
                'name' => 'Crawler',
                QueueEntryWriter::CALLBACK_PARAMETER => GreetingCallback::class,
            ]),
        ];

        $result = $this->get(QueueExecutor::class)->executeQueueItem($queueItem);

        $expected = 'Hello Crawler from TYPO3 ' . (new Typo3Version())->getMajorVersion();
        self::assertSame([
            'content' => json_encode($expected),
        ], $result);
    }
}
