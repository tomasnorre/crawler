<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Middleware;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * @covers \AOE\Crawler\Middleware\FrontendUserAuthenticator
 */
class FrontendUserAuthenticatorTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function getFrontendUserReturnFrontendUser(): void
    {
        $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('frontend.user', $feUser);
        $class = new FrontendUserAuthenticator();
        /** @var FrontendUserAuthentication $user */
        $user = $this->invokeMethod($class, 'getFrontendUser', ['12,13', $request]);

        self::assertEquals(
            '0,-2,12,13',
            $user->user['usergroup']
        );
    }

    private function invokeMethod(&$object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
