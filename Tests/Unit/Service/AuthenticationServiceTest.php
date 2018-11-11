<?php
namespace AOE\Crawler\Tests\Unit\Service;

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

use AOE\Crawler\Service\AuthenticationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class AuthenticationServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Service
 */
class AuthenticationServiceTest extends UnitTestCase
{

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    public function setUp()
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class, ['fetchUserRecord'], [], '', false);
    }

    /**
     * @test
     */
    public function getUserReturnsFalseWhenNoServerVarsSet()
    {
        $this->assertFalse(
            $this->authenticationService->getUser()
        );
    }

    /**
     * @test
     */
    public function getUserReturnsUserObject()
    {
        $_SERVER['HTTP_X_T3CRAWLER'] = 'DummyValue';
        $user = ['username' => '_cli_crawler'];

        $this->authenticationService->expects($this->once())->method('fetchUserRecord')->will($this->returnValue($user));
        $this->assertEquals(
            $user,
            $this->authenticationService->getUser()
        );
    }

    /**
     * @test
     */
    public function authUserReturnsOk200()
    {
        $_SERVER['HTTP_X_T3CRAWLER'] = 'DummyValue';
        $user['username'] = '_cli_crawler';

        $this->assertEquals(
            200,
            $this->authenticationService->authUser($user)
        );
    }

    /**
     * @test
     */
    public function authUserReturnsNotOk100()
    {
        $_SERVER['HTTP_X_T3CRAWLER'] = 'DummyValue';
        $user['username'] = 'not_valid_user';

        $this->assertEquals(
            100,
            $this->authenticationService->authUser($user)
        );
    }

    /**
     * @test
     */
    public function authUserReturnsNotOkCauseHeaderNotSet()
    {
        $user['username'] = '_cli_crawler';
        $this->assertEquals(
            100,
            $this->authenticationService->authUser($user)
        );
    }
}
