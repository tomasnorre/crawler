<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller\Helper;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Controller\Backend\Helper\RequestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(RequestHelper::class)]
class RequestHelperTest extends UnitTestCase
{
    #[Test]
    public function getIntFromRequestFromParsedBody()
    {
        $value = random_int(1, 100);
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getIntFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getIntFromRequestFromQueryParams()
    {
        $value = random_int(1, 100);
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getIntFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getIntFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertSame(0, RequestHelper::getIntFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestFromParsedBody()
    {
        $value = true;
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestFromQueryParams()
    {
        $value = true;
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertSame(false, RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestFromQueryParamsReturnTrueWithCrawl()
    {
        $value = '_crawl';
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame(true, RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }
    #[Test]
    public function getBoolFromRequestFromQueryParamsReturnTrueWithDownload()
    {
        $value = '_download';
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame(true, RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getStringFromRequestFromParsedBody()
    {
        $value = substr(str_shuffle(MD5(microtime())), 0, 10);
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getStringFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getStringFromRequestFromQueryParams()
    {
        $value = substr(str_shuffle(MD5(microtime())), 0, 10);
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getStringFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getStringFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertEmpty(RequestHelper::getStringFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestFromParsedBody()
    {
        $value = [
            'int' => 123,
            'bool' => true,
            'string' => 'hello world',
        ];
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestFromQueryParams()
    {
        $value = [
            'int' => 123,
            'bool' => true,
            'string' => 'hello world',
        ];
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame($value, RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertEmpty(RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

}
