<?php
namespace AOE\Crawler\Tests\Unit\Domain\Model;

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

use AOE\Crawler\Domain\Model\Configuration;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ConfigurationTest
 */
class ConfigurationTest extends UnitTestCase
{
    /**
     * @var Configuration
     */
    protected $subject;


    public function setUp()
    {
        parent::setUp();
        /** @var Configuration subject */
        $this->subject = $this->getMock(Configuration::class, ['dummy'], [], '', false);

    }

    /**
     * @test
     */
    public function setAndGetName()
    {
        $name = 'Default Crawler Configuration';
        $this->subject->setName($name);
        $this->assertSame(
            $name,
            $this->subject->getName()
        );
    }

    /**
     * @test
     */
    public function setAndGetForceSsl()
    {
        $forceSsl = true;
        $this->subject->setForceSsl($forceSsl);
        $this->assertSame(
            $forceSsl,
            $this->subject->isForceSsl()
        );
    }

    /**
     * @test
     */
    public function setAndGetProcessingInstructionFilter()
    {
        $processingInstructionFilter = 'Process Instructions Filter';
        $this->subject->setProcessingInstructionFilter($processingInstructionFilter);
        $this->assertSame(
            $processingInstructionFilter,
            $this->subject->getProcessingInstructionFilter()
        );
    }

    /**
     * @test
     */
    public function setAndGetProcessingInstructionParameters()
    {
        $processingInstructionParameters = 'Process Instructions Parameters';
        $this->subject->setProceessingInstructionParameters($processingInstructionParameters);
        $this->assertSame(
            $processingInstructionParameters,
            $this->subject->getProceessingInstructionParameters()
        );
    }

    /**
     * @test
     */
    public function getAndSetConfiguration()
    {
        $configuration = 'DoThis,AndDoThat';
        $this->subject->setConfiguration($configuration);
        $this->assertSame(
            $configuration,
            $this->subject->getConfiguration()
        );
    }

    /**
     * @test
     */
    public function getAndSetBaseUrl()
    {
        $baseUrl = 'http://www.domain.tld';
        $this->subject->setBaseUrl($baseUrl);
        $this->assertSame(
            $baseUrl,
            $this->subject->getBaseUrl()
        );
    }

    /**
     * @test
     */
    public function getAndSetSysDomainBaseUrl()
    {
        $sysDomainBaseUrl = 'http://www.domain.tld';
        $this->subject->setSysDomainBaseUrl($sysDomainBaseUrl);
        $this->assertSame(
            $sysDomainBaseUrl,
            $this->subject->getSysDomainBaseUrl()
        );
    }

    /**
     * @test
     */
    public function getAndSetPidsOnly()
    {
        $pids = "7,12,20";
        $this->subject->setPidsOnly($pids);
        $this->assertSame(
            $pids,
            $this->subject->getPidsOnly()
        );
    }

    /**
     * @test
     */
    public function getAndSetBeGroups()
    {
        $beGroups = '3,4,5';
        $this->subject->setBeGroups($beGroups);
        $this->assertSame(
            $beGroups,
            $this->subject->getBeGroups()
        );
    }

    /**
     * @test
     */
    public function getAndSetFeGroups()
    {
        $feGroups = '3,4,5';
        $this->subject->setFeGroups($feGroups);
        $this->assertSame(
            $feGroups,
            $this->subject->getFeGroups()
        );
    }

    /**
     * @test
     */
    public function getAndSetRealUrl()
    {
        $realUrl = 1234;
        $this->subject->setRealUrl($realUrl);
        $this->assertSame(
            $realUrl,
            $this->subject->getRealUrl()
        );
    }

    /**
     * @test
     */
    public function getAndSetCHash()
    {
        $chash = 1234;
        $this->subject->setCHash($chash);
        $this->assertSame(
            $chash,
            $this->subject->getCHash()
        );
    }

    /**
     * @test
     */
    public function getAndSetExcludeText()
    {
        $excludeText = 'Excluded:1234';
        $this->subject->setExcludeText($excludeText);
        $this->assertSame(
            $excludeText,
            $this->subject->getExcludeText()
        );
    }

    /**
     * @test
     */
    public function getAndSetRootTemplatePid()
    {
        $rootTemplatePid = 54322;
        $this->subject->setRootTemplatePid($rootTemplatePid);
        $this->assertSame(
            $rootTemplatePid,
            $this->subject->getRootTemplatePid()
        );
    }
}