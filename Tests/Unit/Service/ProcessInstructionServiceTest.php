<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Service\ProcessInstructionService;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\ProcessInstructionService::class)]
class ProcessInstructionServiceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    private ProcessInstructionService $processInstructionService;

    protected function setUp(): void
    {
        $this->processInstructionService = new ProcessInstructionService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('isAllowedDataProvider')]
    public function isAllowReturnsExpectedBoolValue(
        string $piString,
        array $incomingProcInstructions,
        bool $expected
    ): void {
        self::assertEquals(
            $expected,
            $this->processInstructionService->isAllowed($piString, $incomingProcInstructions)
        );
    }

    public static function isAllowedDataProvider(): iterable
    {
        yield 'Not in list' => [
            'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
            'incomingProcInstructions' => ['tx_unknown_extension_instruction'],
            'expected' => false,
        ];
        yield 'In list' => [
            'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
            'incomingProcInstructions' => ['tx_indexedsearch_reindex'],
            'expected' => true,
        ];
        yield 'Twice in list' => [
            'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
            'incomingProcInstructions' => ['tx_indexedsearch_reindex', 'tx_indexedsearch_reindex'],
            'expected' => true,
        ];
        yield 'Empty incomingProcInstructions' => [
            'piString' => '',
            'incomingProcInstructions' => [],
            'expected' => true,
        ];
        yield 'In list CAPITALIZED' => [
            'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
            'incomingProcInstructions' => ['TX_INDEXEDSEARCH_REINDES'],
            'expected' => false,
        ];
    }
}
