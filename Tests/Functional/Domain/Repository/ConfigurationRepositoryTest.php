<?php
namespace AOE\Crawler\Tests\Functional\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ConfigurationRepositoryTest
 */
class ConfigurationRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var ConfigurationRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(ConfigurationRepository::class);
        $this->importDataSet(dirname(__FILE__) . '/../../Fixtures/tx_crawler_configuration.xml');
    }

    /**
     * @test
     */
    public function getCrawlerConfigurationRecords()
    {
        $this->assertSame(
            1,
            $this->subject->countAll()
        );
    }

    /**
     * @test
     */
    public function getConfigurationRecordsPageUid()
    {
        $this->assertSame(
            2,
            $this->subject->getConfigurationRecordsPageUid(5)->count()
        );

        $this->assertSame(
            "Not hidden or deleted",
            $this->subject->getConfigurationRecordsPageUid(5)->getFirst()->getName()
        );

    }
}