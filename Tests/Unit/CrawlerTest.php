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

namespace AOE\Crawler\Tests\Unit;

use AOE\Crawler\Crawler;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\Crawler
 */
class CrawlerTest extends UnitTestCase
{
    /**
     * @var Crawler
     */
    protected $crawler;

    protected function setUp(): void
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo') ?: 'FileNameIsForceIfTempNamReturnedFalse.txt';
        $this->crawler = GeneralUtility::makeInstance(Crawler::class, $filenameWithPath);
    }

    /**
     * @test
     */
    public function setDisabledTest(): void
    {
        // Checking that default the crawler is enabled
        self::assertFalse(
            $this->crawler->isDisabled()
        );

        // Checking that setDisable is default true
        $this->crawler->setDisabled();
        self::assertTrue(
            $this->crawler->isDisabled()
        );

        $this->crawler->setDisabled(true);
        self::assertTrue(
            $this->crawler->isDisabled()
        );

        $this->crawler->setDisabled(false);
        self::assertFalse(
            $this->crawler->isDisabled()
        );
    }
}
