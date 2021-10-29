<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Unit\Domain\Model;

use AOE\Crawler\Domain\Model\Queue;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class QueueTest
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 * @covers \AOE\Crawler\Domain\Model\Queue
 */
class QueueTest extends UnitTestCase
{
    /**
     * @var Queue
     */
    protected $subject;

    /**
     * @test
     */
    public function getterAndSettersTest(): void
    {
        $execTime = 123456;
        $configuration = 'Test Configuration';
        $configurationHash = sha1($configuration);
        $processId = '124';
        $pageId = 543;
        $parameters = 'ParameterOne, ParameterTwo';
        $parametersHash = sha1($parameters);
        $qid = 9838247;
        $isScheduler = false;
        $isProcessScheduled = true;
        $setId = 1234324;
        $resultData = '{row: success}';
        $processIdCompleted = 'as234sa';

        $this->subject = new Queue();
        $this->subject->setExecTime($execTime);
        $this->subject->setConfiguration($configuration);
        $this->subject->setConfigurationHash($configurationHash);
        $this->subject->setProcessId($processId);
        $this->subject->setPageId($pageId);
        $this->subject->setParameters($parameters);
        $this->subject->setParametersHash($parametersHash);
        $this->subject->setQid($qid);
        $this->subject->setScheduled($isScheduler);
        $this->subject->setProcessScheduled($isProcessScheduled);
        $this->subject->setSetId($setId);
        $this->subject->setResultData($resultData);
        $this->subject->setProcessIdCompleted($processIdCompleted);

        self::assertEquals(
            $execTime,
            $this->subject->getExecTime()
        );

        self::assertSame(
            $configuration,
            $this->subject->getConfiguration()
        );

        self::assertSame(
            $configurationHash,
            $this->subject->getConfigurationHash()
        );

        self::assertSame(
            $processId,
            $this->subject->getProcessId()
        );

        self::assertSame(
            $pageId,
            $this->subject->getPageId()
        );

        self::assertSame(
            $parameters,
            $this->subject->getParameters()
        );

        self::assertSame(
            $parametersHash,
            $this->subject->getParametersHash()
        );

        self::assertSame(
            $qid,
            $this->subject->getQid()
        );

        self::assertFalse($this->subject->isScheduled());
        self::assertTrue($this->subject->isProcessScheduled());

        self::assertSame(
            $setId,
            $this->subject->getSetId()
        );

        self::assertSame(
            $resultData,
            $this->subject->getResultData()
        );

        self::assertSame(
            $processIdCompleted,
            $this->subject->getProcessIdCompleted()
        );
    }
}
