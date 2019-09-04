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

use AOE\Crawler\Hooks\ProcessCleanUpHook;
use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ProcessServiceTest extends FunctionalTestCase
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ProcessService
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/crawler/Tests/Functional/Fixtures/Extensions/typo3_console',
        'typo3conf/ext/crawler'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ProcessCleanUpHook subject */
        $this->subject = $this->objectManager->get(ProcessService::class);
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getCrawlerCliPathReturnsStringWithoutComposer()
    {
        // Renaming the composer.json to get the case without composer.
        rename(getcwd() . '/composer.json', getcwd() . '/composer.bak');
        $this->assertFileNotExists(getcwd() . '/composer.json');

        $this->assertContains(
            'typo3conf/ext/typo3_console/typo3cms crawler:crawlqueue',
            $this->subject->getCrawlerCliPath()
        );

        rename(getcwd() . '/composer.bak', getcwd() . '/composer.json');
        $this->assertFileExists(getcwd() . '/composer.json');
    }
}
