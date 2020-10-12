<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Domain\Repository;

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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class ProcessRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'fluid', 'extbase'];

    /**
     * @var ProcessRepository
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $this->objectManager->get(ProcessRepository::class);

        $configuration = [
            'sleepTime' => '1000',
            'sleepAfterFinish' => '10',
            'countInARun' => '100',
            'purgeQueueDays' => '14',
            'processLimit' => '1',
            'processMaxRunTime' => '300',
            'maxCompileUrls' => '10000',
            'processDebug' => '0',
            'processVerbose' => '0',
            'crawlHiddenPages' => '0',
            'phpPath' => '/usr/bin/php',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;

        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_crawler_process.xml');
    }

    /**
     * @test
     */
    public function findAllReturnsAll(): void
    {
        self::assertSame(
            6,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @test
     */
    public function findAllActiveReturnsActive(): void
    {
        self::assertSame(
            3,
            $this->subject->findAllActive()->count()
        );
    }

    /**
     * @test
     */
    public function removeByProcessId(): void
    {
        self::assertSame(
            6,
            $this->subject->findAll()->count()
        );

        $this->subject->removeByProcessId('1002');

        self::assertSame(
            5,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @test
     */
    public function countActive(): void
    {
        self::assertSame(
            3,
            $this->subject->findAllActive()->count()
        );
    }

    /**
     * @test
     */
    public function countNotTimeouted(): void
    {
        self::assertSame(
            2,
            $this->subject->countNotTimeouted(11)
        );
    }

    /**
     * @test
     */
    public function countAll(): void
    {
        self::assertSame(
            6,
            $this->subject->countAll()
        );
    }

    /**
     * @test
     */
    public function getActiveProcessesOlderThanOneOHour(): void
    {
        $expected = [
            ['process_id' => '1000', 'system_process_id' => 0],
            ['process_id' => '1001', 'system_process_id' => 0],
            ['process_id' => '1002', 'system_process_id' => 0],
            ['process_id' => '1005', 'system_process_id' => 0],
        ];

        self::assertSame(
            $expected,
            $this->subject->getActiveProcessesOlderThanOneHour()
        );
    }

    /**
     * @test
     */
    public function getActiveOrphanProcesses(): void
    {
        $expected = [
            ['process_id' => '1000', 'system_process_id' => 0],
            ['process_id' => '1001', 'system_process_id' => 0],
            ['process_id' => '1002', 'system_process_id' => 0],
            ['process_id' => '1005', 'system_process_id' => 0],
        ];

        self::assertSame(
            $expected,
            $this->subject->getActiveOrphanProcesses()
        );
    }

    /**
     * @test
     */
    public function deleteProcessesWithoutItemsAssigned(): void
    {
        $countBeforeDelete = $this->subject->findAll()->count();
        $expectedProcessesToBeDeleted = 3;
        $this->subject->deleteProcessesWithoutItemsAssigned();

        self::assertSame(
            $this->subject->findAll()->count(),
            $countBeforeDelete - $expectedProcessesToBeDeleted
        );
    }

    /**
     * @test
     */
    public function deleteProcessesMarkedAsDeleted(): void
    {
        $countBeforeDelete = $this->subject->findAll()->count();
        $expectedProcessesToBeDeleted = 3;
        $this->subject->deleteProcessesMarkedAsDeleted();

        self::assertSame(
            $this->subject->findAll()->count(),
            $countBeforeDelete - $expectedProcessesToBeDeleted
        );
    }

    /**
     * @test
     */
    public function markRequestedProcessesAsNotActive(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] = [];
        self::assertEquals(
            3,
            $this->subject->findAllActive()->count()
        );

        $processIds = ['1001', '1002'];
        $this->subject->markRequestedProcessesAsNotActive($processIds);

        self::assertEquals(
            1,
            $this->subject->findAllActive()->count()
        );
    }

    /**
     * @test
     */
    public function updateProcessAssignItemsCount(): void
    {
        $processBefore = $this->subject->findByProcessId('1002');
        self::assertEquals(
            1,
            $processBefore['assigned_items_count']
        );

        $this->subject->updateProcessAssignItemsCount(10, '1002');

        $processAfter = $this->subject->findByProcessId('1002');
        self::assertEquals(
            10,
            $processAfter['assigned_items_count']
        );
    }
}
