<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional;

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

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait BackendRequestTestTrait
{
    use ProphecyTrait;

    /**
     * This is needed as long as TYPO3 Backend does not bootstrap a request object which can be handed into modules,
     * and the Functional Test Suite cannot boot up a Backend with all the middlewares in place (thus, loading
     * all routes and adding the current route from the requested URL).
     */
    protected function setupBackendRequest(): void
    {
        $route = new Route('/module/web/list', []);
        $router = GeneralUtility::makeInstance(Router::class);
        $router->addRoute('module_web_info', $route);
        $request = new ServerRequest('https://example.com/typo3/index.php');
        $request = $request->withQueryParams([
            'route' => '/web_info',
        ]);
        $request = $request->withAttribute('route', $route);

        $typo3version = new Typo3Version();
        if ($typo3version->getMajorVersion() >=14) {
            $request = $request->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
        }

        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
}
