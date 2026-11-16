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
    protected bool $resetSingletonInstances = true;

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
    public function getIntFromRequestFromQueryParamsIntAsStringValue()
    {
        $value = '1000';
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertSame(1000, RequestHelper::getIntFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getIntFromRequestWithBothParsedBodyAndQueryParams()
    {
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => 2000,
        ]);
        $serverRequest = $serverRequest->withQueryParams([
            'value' => 1000,
        ]);
        $this->assertSame(2000, RequestHelper::getIntFromRequest($serverRequest, 'value'));
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
        $this->assertTrue(RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestFromQueryParams()
    {
        $value = true;
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertTrue(RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestWithBothParsedBodyAndQueryParams()
    {
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => true,
        ]);
        $serverRequest = $serverRequest->withQueryParams([
            'value' => false,
        ]);
        $this->assertTrue(RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestFromQueryParamsExpectFalse()
    {
        $value = false;
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $value,
        ]);
        $this->assertFalse(RequestHelper::getBoolFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getBoolFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertFalse(RequestHelper::getBoolFromRequest($serverRequest, 'value'));
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
    public function getStringFromRequestWithBothParsedBodyAndQueryParams()
    {
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => 'parsedBody',
        ]);
        $serverRequest = $serverRequest->withQueryParams([
            'value' => 'queryParams',
        ]);
        $this->assertSame('parsedBody', RequestHelper::getStringFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getStringFromRequestStringAsIntValue()
    {
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withQueryParams([
            'value' => 1001,
        ]);
        $this->assertSame('1001', RequestHelper::getStringFromRequest($serverRequest, 'value'));
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
    public function getArrayFromRequestWithBothParsedBodyAndQueryParams()
    {
        $parsedBody = [
            'int' => 123,
            'bool' => true,
            'string' => 'parsedBody',
        ];
        $queryParams = [
            'int' => 321,
            'bool' => false,
            'string' => 'queryParams',
        ];
        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody([
            'value' => $parsedBody,
        ]);
        $serverRequest = $serverRequest->withQueryParams([
            'value' => $queryParams,
        ]);
        $this->assertSame($parsedBody, RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestReturnDefault()
    {
        $serverRequest = new ServerRequest();
        $this->assertEmpty(RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getIntFromRequestReturnsGivenDefault(): void
    {
        self::assertSame(30, RequestHelper::getIntFromRequest(new ServerRequest(), 'value', 30));
    }

    #[Test]
    public function getIntFromRequestReturnsDefaultForArrayValue(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'value' => ['5'],
        ]);

        self::assertSame(30, RequestHelper::getIntFromRequest($serverRequest, 'value', 30));
    }

    #[Test]
    public function getStringFromRequestReturnsDefaultForArrayValue(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'value' => ['5'],
        ]);

        self::assertSame('fallback', RequestHelper::getStringFromRequest($serverRequest, 'value', 'fallback'));
    }

    #[Test]
    public function getValueFallsBackToQueryParamsIfParsedBodyHasNoValue(): void
    {
        $serverRequest = (new ServerRequest())
            ->withParsedBody([
                'other' => 'parsedBody',
                'value' => null,
            ])
            ->withQueryParams([
                'value' => 'queryParams',
            ]);

        self::assertSame('queryParams', RequestHelper::getStringFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getValueUsesQueryParamsIfParsedBodyIsNoArray(): void
    {
        $serverRequest = (new ServerRequest())
            ->withParsedBody(new \stdClass())
            ->withQueryParams([
                'value' => '7',
            ]);

        self::assertSame(7, RequestHelper::getIntFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestDecodesJsonString(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'value' => '{"int":123,"string":"hello world"}',
        ]);

        self::assertSame([
            'int' => 123,
            'string' => 'hello world',
        ], RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestReturnsEmptyArrayForInvalidJson(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'value' => 'no json',
        ]);

        self::assertSame([], RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }

    #[Test]
    public function getArrayFromRequestReturnsEmptyArrayForScalarValue(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'value' => 5,
        ]);

        self::assertSame([], RequestHelper::getArrayFromRequest($serverRequest, 'value'));
    }
}
