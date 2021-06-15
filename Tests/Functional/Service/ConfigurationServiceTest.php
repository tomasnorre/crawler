<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class ConfigurationServiceTest extends FunctionalTestCase
{
    /**
     * @var ConfigurationService
     */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = $this->createPartialMock(ConfigurationService::class, []);
    }

    /**
     * @test
     */
    public function expandExcludeStringReturnsArraysOfIntegers(): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();

        $excludeStringArray = $this->subject->expandExcludeString('1,2,4,6,8');

        foreach ($excludeStringArray as $excluded) {
            self::assertIsInt($excluded);
        }
    }
}
