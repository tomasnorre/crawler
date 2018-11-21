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

use AOE\Crawler\Domain\Model\Queue;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class QueueTest
 * @package AOE\Crawler\Tests\Unit\Domain\Model
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
    public function getterAndSettersTest()
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

        /** @var Queue subject */
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

        $this->assertEquals(
            $execTime,
            $this->subject->getExecTime()
        );

        $this->assertSame(
            $configuration,
            $this->subject->getConfiguration()
        );

        $this->assertSame(
            $configurationHash,
            $this->subject->getConfigurationHash()
        );

        $this->assertSame(
            $processId,
            $this->subject->getProcessId()
        );

        $this->assertSame(
            $pageId,
            $this->subject->getPageId()
        );

        $this->assertSame(
            $parameters,
            $this->subject->getParameters()
        );

        $this->assertSame(
            $parametersHash,
            $this->subject->getParametersHash()
        );

        $this->assertSame(
            $qid,
            $this->subject->getQid()
        );

        $this->assertFalse($this->subject->isScheduled());
        $this->assertTrue($this->subject->isProcessScheduled());

        $this->assertSame(
            $setId,
            $this->subject->getSetId()
        );

        $this->assertSame(
            $resultData,
            $this->subject->getResultData()
        );

        $this->assertSame(
            $processIdCompleted,
            $this->subject->getProcessIdCompleted()
        );
    }
}
