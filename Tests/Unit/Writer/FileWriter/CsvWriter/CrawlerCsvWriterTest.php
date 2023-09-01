<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Writer\FileWriter\CsvWriter;

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

use AOE\Crawler\Writer\FileWriter\CsvWriter\CrawlerCsvWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \AOE\Crawler\Writer\FileWriter\CsvWriter\CrawlerCsvWriter
 */
class CrawlerCsvWriterTest extends UnitTestCase
{
    protected \AOE\Crawler\Writer\FileWriter\CsvWriter\CrawlerCsvWriter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(CrawlerCsvWriter::class);
    }

    /**
     * @test
     */
    public function arrayToCsvTest(): void
    {
        $records = [];
        $records[] = [
            'Page Title' => 'Home',
            'Page Uid' => 1,
        ];

        // Done to make sure that the reset() in the function is used, to reset the array
        // to it's start pointer again.
        next($records);

        self::assertEquals(
            '"Page Title","Page Uid"' . chr(13) . chr(10) . '"Home",1',
            $this->subject->arrayToCsv($records)
        );
    }
}
