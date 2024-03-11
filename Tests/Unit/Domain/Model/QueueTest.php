<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Model\Queue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class QueueTest
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Queue::class)]
class QueueTest extends UnitTestCase
{
    protected ?\AOE\Crawler\Domain\Model\Queue $subject = null;

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getterAndSettersTest(): void
    {
        $execTime = 123456;
        $configuration = 'Test Configuration';
        $configurationHash = sha1($configuration);
        $processId = '124';
        $pageId = 543;
        $parameters = 'ParameterOne, ParameterTwo';
        $parametersHash = sha1($parameters);
        $qid = 9_838_247;
        $isScheduler = false;
        $isProcessScheduled = true;
        $setId = 1_234_324;
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

        self::assertEquals($execTime, $this->subject->getExecTime());

        self::assertSame($configuration, $this->subject->getConfiguration());

        self::assertSame($configurationHash, $this->subject->getConfigurationHash());

        self::assertSame($processId, $this->subject->getProcessId());

        self::assertSame($pageId, $this->subject->getPageId());

        self::assertSame($parameters, $this->subject->getParameters());

        self::assertSame($parametersHash, $this->subject->getParametersHash());

        self::assertSame($qid, $this->subject->getQid());

        self::assertFalse($this->subject->isScheduled());
        self::assertTrue($this->subject->isProcessScheduled());

        self::assertSame($setId, $this->subject->getSetId());

        self::assertSame($resultData, $this->subject->getResultData());

        self::assertSame($processIdCompleted, $this->subject->getProcessIdCompleted());
    }
}
