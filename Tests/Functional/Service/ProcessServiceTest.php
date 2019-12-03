<?php

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
use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessServiceTest extends FunctionalTestCase
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
        parent::setUp();
        $this->subject = $this->createPartialMock(ProcessService::class, ['dummyMethod']);
        $this->crawlerController = $this->createPartialMock(CrawlerController::class, ['dummyMethod']);
    }

    /**
     * @test
     */
    public function getCrawlerCliPathReturnsString()
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
     * @expectedException \Exception
     */
    public function getCrawlerCliPathThrowsException()
    {
        $this->subject->getCrawlerCliPath();
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
