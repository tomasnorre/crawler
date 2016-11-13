<?php
namespace AOE\Crawler\Tests\Functional\Utility;

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

use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TypoScriptUtilityTest
 *
 * @package AOE\Crawler\Tests\Functional\Utility
 */
class TypoScriptUtilityTest extends FunctionalTestCase
{
    /**
     * @var $subject \AOE\Crawler\Utility\TypoScriptUtility
     * @inject
     */
    var $subject = null;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = array('typo3conf/ext/crawler');

    public function setUp()
    {
        parent::setUp();

        // Include Fixtures
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_template.xml');

        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->subject = $this->objectManager->get('AOE\\Crawler\\Utility\\TypoScriptUtility');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPageUidForTypoScriptRootTemplateInRootLineReturnsIntegerValueFive()
    {
        $pageUid = 7;
        $this->assertEquals(
            5,
            $this->subject->getPageUidForTypoScriptRootTemplateInRootLine($pageUid)
        );
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function getPageUidForTypoScriptRootTemplateInRootLineThrowsException()
    {
        // PageUid 15, doesn't exist therefore exception thrown.
        $pageUid = 15;
        $this->assertEquals(
            5,
            $this->subject->getPageUidForTypoScriptRootTemplateInRootLine($pageUid)
        );
    }
}