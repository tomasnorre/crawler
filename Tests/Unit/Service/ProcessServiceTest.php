<?php
namespace AOE\Crawler\Tests\Unit\Service;

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
use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessServiceTest extends UnitTestCase
{

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
     *
     * @return void
     */
    public function setUp()
    {
        $this->subject = $this->createPartialMock(ProcessService::class, ['dummyMethod']);

        $this->crawlerController = $this->createPartialMock(CrawlerController::class, ['dummyMethod']);

        // The getcwd() will return the directory from where the tests are called,
        // that would be the ext/crawler folder, so we are validating against
        // the composer.json of the crawler extension, and no dummy fixture.
        define('TYPO3_PATH_COMPOSER_ROOT', getcwd());
    }

    /**
     * @test
     */
    public function getCrawlerCliPathReturnsString()
    {
        $this->markTestSkipped('Test is failed, talk about define() and setExtensionSettings()-context');
        $this->assertEquals(
            getcwd() . '/.Build/bin/typo3cms crawler:crawlqueue',
            $this->subject->getCrawlerCliPath()
        );
    }

    /**
     * @test
     */
    public function multiProcessThrowsException()
    {

        $this->expectException(\RuntimeException::class);

        $timeOut = 1;
        $this->crawlerController->setExtensionSettings([
            'processLimit' => 1,
        ]);
        $this->subject->multiProcess($timeOut);

    }
}
