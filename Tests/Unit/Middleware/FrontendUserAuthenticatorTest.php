<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Middleware;

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

use AOE\Crawler\Middleware\FrontendUserAuthenticator;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FrontendUserAuthenticatorTest extends UnitTestCase
{
    /**
     * @var FrontendUserAuthenticator
     */
    protected $subject;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = md5('this_is_an_insecure_encryption_key');
        $this->subject = self::getAccessibleMock(FrontendUserAuthenticator::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     * @dataProvider isRequestHashMatchingQueueRecordDataProvider
     */
    public function isRequestHashMatchingQueueRecord($queueRecord, $hash, $expected): void
    {
        self::assertSame(
            $this->subject->_call('isRequestHashMatchingQueueRecord', $queueRecord, $hash),
            $expected
        );
    }

    public function isRequestHashMatchingQueueRecordDataProvider(): array
    {
        $queueRecord = [
            'qid' => '1234',
            'set_id' => '4321',
        ];

        // Faking the TYPO3 Encryption key as the DataProvider doesn't know about the TYPO3_CONF array
        $encryptionKey = md5('this_is_an_insecure_encryption_key');

        return [
            'Hash match' => [
                'queueRecord' => $queueRecord,
                'hash' => md5($queueRecord['qid'] . '|' . $queueRecord['set_id'] . '|' . $encryptionKey),
                'expected' => true,
            ],
            'Hash does not match' => [
                'queueRecord' => $queueRecord,
                'hash' => md5('qid' . '|' . 'set_id' . '|' . $encryptionKey),
                'expected' => false,
            ],
            'queueRecord is not an array, there returning false' => [
                'queueRecord' => null,
                'hash' => '',
                'expected' => false,
            ],
        ];
    }
}
