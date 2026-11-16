<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\EventListener;

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

use AOE\Crawler\EventListener\ShouldUseCachedPageDataIfAvailableEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ShouldUseCachedPageDataIfAvailableEventListener::class)]
class ShouldUseCachedPageDataIfAvailableEventListenerTest extends UnitTestCase
{
    #[Test]
    public function keepsCachedPageDataForOtherRequests(): void
    {
        $event = new ShouldUseCachedPageDataIfAvailableEvent(new ServerRequest(), true);

        (new ShouldUseCachedPageDataIfAvailableEventListener())($event);

        self::assertTrue($event->shouldUseCachedPageData());
    }

    #[Test]
    public function disablesCachedPageDataForCrawlerRequests(): void
    {
        $event = new ShouldUseCachedPageDataIfAvailableEvent(
            (new ServerRequest())->withAttribute('tx_crawler', [
                'url' => 'https://example.com/',
            ]),
            true
        );

        (new ShouldUseCachedPageDataIfAvailableEventListener())($event);

        self::assertFalse($event->shouldUseCachedPageData());
    }
}
