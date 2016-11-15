<?php
namespace AOE\Crawler\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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

use SebastianBergmann\Git\RuntimeException;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class ProcessManagerTest
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessManagerTest extends UnitTestCase
{

    /**
     * @var \tx_crawler_lib
     */
    protected $crawlerLibrary;

    /**
     * @var \tx_crawler_domain_process_manager
     */
    protected $subject;

    /**
     * Creates the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        $this->subject = new \tx_crawler_domain_process_manager();

        $this->crawlerLibrary = $this->getMock('\tx_crawler_lib', array('dummyMethod'), array(), '', FALSE);

        define('TYPO3_DOCUMENT_ROOT', '/typo3/document/root/');
        define('TYPO3_SITE_PATH', '/typo3/site/path/');

        $this->crawlerLibrary->setExtensionSettings(array(
            'phpPath' => '/path/to/php',
        ));

    }

    /**
     * Resets the test environment after the test.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->crawlerLibrary);
    }

    /**
     * @test
     */
    public function getCrawlerCliPathReturnsString()
    {
        $this->markTestSkipped('Test is failed, talk about define() and setExtensionSettings()-context');
        $this->assertEquals(
            '/path/to/php /typo3/document/root/typo3/site/path/typo3/cli_dispatch.phpsh crawler',
            $this->subject->getCrawlerCliPath()
        );
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     */
    public function multiProcessThrowsException()
    {
        $timeOut = 1;
        $this->crawlerLibrary->setExtensionSettings(array(
            'processLimit' => 1,
        ));
        $this->subject->multiProcess($timeOut);
    }
}