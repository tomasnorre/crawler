<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessServiceTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    /**
     * @var ProcessService
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Extension Settings
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpBinary' => 'php',
        ];

        $this->subject = GeneralUtility::makeInstance(ObjectManager::class)->get(ProcessService::class);
        $this->crawlerController = $this->createPartialMock(CrawlerController::class, ['dummyMethod']);

        $mockedProcessRepository = $this->createPartialMock(ProcessRepository::class, ['countNotTimeouted']);
        $mockedProcessRepository->expects($this->exactly(2))->method('countNotTimeouted')->withConsecutive(
            [$this->equalTo(1), $this->greaterThan(1)],
            [$this->equalTo(2), $this->greaterThan(2)]
        );

        $this->subject->processRepository = $mockedProcessRepository;
    }

    /**
     * @test
     */
    public function getCrawlerCliPathReturnsString(): void
    {

        // Check with phpPath set
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => '/usr/local/bin/foobar-binary-to-be-able-to-differ-the-test',
            'phpBinary' => '/usr/local/bin/php74',
        ];
        self::assertContains(
            '/usr/local/bin/foobar-binary-to-be-able-to-differ-the-test',
            $this->subject->getCrawlerCliPath()
        );

        // Check without phpPath set
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpBinary' => 'php',

        ];
        self::assertContains(
            'php',
            $this->subject->getCrawlerCliPath()
        );
    }

    /**
     * @test
     */
    public function getCrawlerCliPathThrowsException(): void
    {
        $this->expectExceptionMessage('Return value of AOE\Crawler\Configuration\ExtensionConfigurationProvider::getExtensionConfiguration() must be of the type array');
        $this->subject->getCrawlerCliPath();
    }

    /**
     * @test
     */
    public function multiProcessThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $timeOut = 1;
        $this->crawlerController->setExtensionSettings([
            'processLimit' => 1,
        ]);
        $this->subject->multiProcess($timeOut);
    }

    /**
     * @test
     */
    public function startProcess(): void
    {
        self::assertTrue($this->subject->startProcess());
    }
}
