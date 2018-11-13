<?php
namespace AOE\Crawler\Tests\Functional\Backend;

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

use AOE\Crawler\Backend\BackendModule;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class BackendModuleTest
 */
class BackendModuleTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['beuser', 'cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var BackendModule
     */
    protected $subject;

    /**
     * Creates the test environment.
     *
     */
    public function setUp()
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/be_users.xml');
        $this->subject = $objectManager->get(BackendModule::class);
    }

    /**
     * @test
     */
    public function isCrawlerUserAvailableExpectsTrue()
    {
        $this->assertTrue(
            $this->invokeMethod($this->subject, 'isCrawlerUserAvailable')
        );
    }

    /**
     * @test
     */
    public function isCrawlerUserNotAdminExpectsFalse()
    {
        $this->assertFalse(
            $this->invokeMethod($this->subject, 'isCrawlerUserAdmin')
        );
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

}