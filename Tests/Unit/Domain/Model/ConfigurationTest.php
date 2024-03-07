<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Model\Configuration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \AOE\Crawler\Domain\Model\Configuration
 */
class ConfigurationTest extends UnitTestCase
{
    protected \AOE\Crawler\Domain\Model\Configuration $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->createPartialMock(Configuration::class, []);
    }

    /**
     * @test
     */
    public function setterAndGetters(): void
    {
        $name = 'Default Configuration';
        $forceSsl = 1;
        $processInstructionFilter = 'tx_indexedsearch_reindex';
        $processInstructionParameters = 'tx_staticpub_publish.publishDirForResources=typo3temp/staticpubresources/';
        $configuration = '&L=[|1|2|3]';
        $baseUrl = 'https://www.example.com';
        $pidOnly = '51,53';
        $beGroups = '2,3';
        $feGroups = '1';
        $excludes = '6+';

        $this->subject->setName($name);
        $this->subject->setForceSsl($forceSsl);
        $this->subject->setProcessingInstructionFilter($processInstructionFilter);
        $this->subject->setProcessingInstructionParameters($processInstructionParameters);
        $this->subject->setConfiguration($configuration);
        $this->subject->setBaseUrl($baseUrl);
        $this->subject->setPidsOnly($pidOnly);
        $this->subject->setBeGroups($beGroups);
        $this->subject->setFeGroups($feGroups);
        $this->subject->setExclude($excludes);

        self::assertEquals($name, $this->subject->getName());

        self::assertEquals($forceSsl, $this->subject->isForceSsl());

        self::assertEquals($processInstructionFilter, $this->subject->getProcessingInstructionFilter());

        self::assertEquals($processInstructionParameters, $this->subject->getProcessingInstructionParameters());

        self::assertEquals($configuration, $this->subject->getConfiguration());

        self::assertEquals($baseUrl, $this->subject->getBaseUrl());

        self::assertEquals($pidOnly, $this->subject->getPidsOnly());

        self::assertEquals($beGroups, $this->subject->getBeGroups());

        self::assertEquals($feGroups, $this->subject->getFeGroups());

        self::assertEquals($excludes, $this->subject->getExclude());
    }

    public function injectSubject(Configuration $subject): void
    {
        $this->subject = $subject;
    }
}
