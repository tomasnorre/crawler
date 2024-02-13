<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Controller;

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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Value\QueueFilter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CrawlerControllerTest
 *
 * @package AOE\Crawler\Tests\Functional\Controller
 */
class CrawlerControllerTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use BackendRequestTestTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $subject;

    protected function setUp(): void
    {
        $this->setupBackendRequest();

        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_process.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tt_content.xml');

        $mockedQueueRepository = $this->createMock(QueueRepository::class);
        $mockedProcessRepository = $this->createMock(ProcessRepository::class);
        $mockedIconFactory = $this->createMock(IconFactory::class);

        $this->subject = $this->getAccessibleMock(
            CrawlerController::class,
            ['dummy'],
            [$mockedQueueRepository, $mockedProcessRepository, $mockedIconFactory]
        );
    }

    /**
     * @test
     */
    public function getConfigurationsForBranch(): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();

        $configurationsForBranch = $this->subject->getConfigurationsForBranch(5, 99);

        self::assertNotEmpty($configurationsForBranch);
        self::assertCount(4, $configurationsForBranch);

        // sort is done as MySQL and SQLite doesn't sort the same way even though sorting is by "name ASC"
        sort($configurationsForBranch);
        $expected = [
            'default',
            'Not hidden or deleted',
            'Not hidden or deleted - uid 5',
            'Not hidden or deleted - uid 6',
        ];
        sort($expected);

        self::assertEquals($expected, $configurationsForBranch);
    }

    /**
     * @test
     * @dataProvider addUrlDataProvider
     */
    public function addUrl(
        int $id,
        string $url,
        array $subCfg,
        int $tstamp,
        string $configurationHash,
        bool $skipInnerDuplicationCheck,
        array $mockedDuplicateRowResult,
        bool $registerQueueEntriesInternallyOnly,
        bool $expected
    ): void {
        $typo3MajorVersion = (new Typo3Version())->getMajorVersion();
        if ($typo3MajorVersion <= 11) {
            $mockedQueueRepository = $this->getAccessibleMock(
                QueueRepository::class,
                ['getDuplicateQueueItemsIfExists'],
                [GeneralUtility::makeInstance(ObjectManager::class)]
            );
        } else {
            $mockedQueueRepository = $this->getAccessibleMock(
                QueueRepository::class,
                ['getDuplicateQueueItemsIfExists']
            );
        }

        $mockedQueueRepository->expects($this->any())->method('getDuplicateQueueItemsIfExists')->willReturn(
            $mockedDuplicateRowResult
        );

        $mockedCrawlerController = $this->getAccessibleMock(CrawlerController::class, ['dummy']);

        $mockedCrawlerController->_set('registerQueueEntriesInternallyOnly', $registerQueueEntriesInternallyOnly);
        $mockedCrawlerController->_set('queueRepository', $mockedQueueRepository);

        self::assertEquals(
            $expected,
            $mockedCrawlerController->addUrl(
                $id,
                $url,
                $subCfg,
                $tstamp,
                $configurationHash,
                $skipInnerDuplicationCheck
            )
        );
    }

    public function addUrlDataProvider(): iterable
    {
        yield 'Queue entry added' => [
            'id' => 0,
            'url' => '',
            'subCfg' => [
                'key' => 'some-key',
                'procInstrFilter' => 'tx_crawler_post',
                'procInstrParams.' => [
                    'action' => true,
                ],
                'userGroups' => '12,14',
            ],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => false,
            'mockedDuplicateRowResult' => [],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => true,
        ];
        yield 'Queue entry is NOT added, due to duplication check return not empty array (mocked)' => [
            'id' => 0,
            'url' => '',
            'subCfg' => ['key' => 'some-key'],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => false,
            'mockedDuplicateRowResult' => ['duplicate-exists' => true],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => false,
        ];
        yield 'Queue entry is added, due to duplication is ignored' => [
            'id' => 0,
            'url' => '',
            'subCfg' => ['key' => 'some-key'],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => true,
            'mockedDuplicateRowResult' => ['duplicate-exists' => true],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => true,
        ];
        yield 'Queue entry is NOT added, due to registerQueueEntriesInternalOnly' => [
            'id' => 0,
            'url' => '',
            'subCfg' => ['key' => 'some-key'],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => true,
            'mockedDuplicateRowResult' => ['duplicate-exists' => true],
            'registerQueueEntriesInternallyOnly' => true,
            'expected' => false,
        ];
    }

    public function getLogEntriesForSetIdDataProvider(): iterable
    {
        yield 'Do Flush' => [
            'setId' => 456,
            'filter' => '',
            'doFlush' => true,
            'doFullFlush' => false,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
        yield 'Do Full Flush' => [
            'setId' => 456,
            'filter' => '',
            'doFlush' => true,
            'doFullFlush' => true,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
        yield 'Check that doFullFlush do not flush if doFlush is not true' => [
            'setId' => 456,
            'filter' => '',
            'doFlush' => false,
            'doFullFlush' => true,
            'itemsPerPage' => 5,
            'expected' => [[
                'qid' => '8',
                'page_id' => '3',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '456',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1007',
                'process_id_completed' => 'asdfgh',
                'configuration' => 'ThirdConfiguration',
            ]],
        ];
        yield 'Get entries for set_id 456' => [
            'setId' => 456,
            'filter' => '',
            'doFlush' => false,
            'doFullFlush' => false,
            'itemsPerPage' => 1,
            'expected' => [[
                'qid' => '8',
                'page_id' => '3',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '456',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1007',
                'process_id_completed' => 'asdfgh',
                'configuration' => 'ThirdConfiguration',
            ]],
        ];
        yield 'Do Flush Pending' => [
            'setId' => 456,
            'filter' => 'pending',
            'doFlush' => true,
            'doFullFlush' => false,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
        yield 'Do Flush Finished' => [
            'setId' => 456,
            'filter' => 'finished',
            'doFlush' => true,
            'doFullFlush' => false,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
    }

    public function getLogEntriesForPageIdDataProvider(): iterable
    {
        yield 'Do Flush' => [
            'id' => 1002,
            'filter' => new QueueFilter(),
            'doFlush' => true,
            'doFullFlush' => false,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
        yield 'Do Full Flush' => [
            'id' => 1002,
            'filter' => new QueueFilter(),
            'doFlush' => true,
            'doFullFlush' => true,
            'itemsPerPage' => 5,
            'expected' => [],
        ];
        yield 'Check that doFullFlush do not flush if doFlush is not true' => [
            'id' => 2,
            'filter' => new QueueFilter(),
            'doFlush' => false,
            'doFullFlush' => true,
            'itemsPerPage' => 5,
            'expected' => [[
                'qid' => '6',
                'page_id' => '2',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '7b6919e533f334550b6f19034dfd2f81',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '123',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1006',
                'process_id_completed' => 'qwerty',
                'configuration' => 'SecondConfiguration',
            ]],
        ];
        yield 'Get entries for page_id 2001' => [
            'id' => 2,
            'filter' => new QueueFilter(),
            'doFlush' => false,
            'doFullFlush' => false,
            'itemsPerPage' => 1,
            'expected' => [[
                'qid' => '6',
                'page_id' => '2',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '7b6919e533f334550b6f19034dfd2f81',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '123',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1006',
                'process_id_completed' => 'qwerty',
                'configuration' => 'SecondConfiguration',
            ]],
        ];
    }
}
