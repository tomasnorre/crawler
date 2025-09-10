<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Hooks;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Hooks\ProcessCleanUpHook;
use AOE\Crawler\Process\Cleaner\OldProcessCleaner;
use AOE\Crawler\Process\Cleaner\OrphanProcessCleaner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @package AOE\Crawler\Tests\Functional\Hooks
 */
class ProcessCleanUpHookTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private ProcessCleanUpHook $subject;

    /**
     * @var OrphanProcessCleaner&MockObject
     */
    private $orphanCleaner;

    /**
     * @var OldProcessCleaner&MockObject
     */
    private $oldCleaner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orphanCleaner = $this->createMock(OrphanProcessCleaner::class);
        $this->oldCleaner = $this->createMock(OldProcessCleaner::class);

        $this->subject = new ProcessCleanUpHook($this->orphanCleaner, $this->oldCleaner);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function crawlerInitCallsBothCleaners(): void
    {
        $this->orphanCleaner->expects(self::once())->method('clean');
        $this->oldCleaner->expects(self::once())->method('clean');

        $this->subject->crawler_init();
    }
}
