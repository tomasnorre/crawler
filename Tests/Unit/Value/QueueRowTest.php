<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Value;

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

use AOE\Crawler\Value\QueueRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Value\QueueRow::class)]
class QueueRowTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructionDefaultValues(): void
    {
        $queueRow = new QueueRow();
        self::assertEmpty($queueRow->pageTitle);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function gettersAndSetters(): void
    {
        $pageTitle = 'Page Title';
        $pageTitleHtml = '<h1>Title</h1>';
        $message = 'This is a message';
        $configurationKey = 'default';
        $parameterConfig = 'string';
        $valueExpanded = 'string';
        $urls = 'https://www.example.com';
        $options = ['option' => 'value'];
        $parameters = 'parameters';

        $queueRow = new QueueRow($pageTitle);
        $queueRow->setPageTitleHTML($pageTitleHtml);
        $queueRow->setMessage($message);
        $queueRow->setConfigurationKey($configurationKey);
        $queueRow->setParameterConfig($parameterConfig);
        $queueRow->setValuesExpanded($valueExpanded);
        $queueRow->setUrls($urls);
        $queueRow->setOptions($options);
        $queueRow->setParameters($parameters);

        self::assertEquals($pageTitle, $queueRow->pageTitle);

        self::assertEquals($pageTitleHtml, $queueRow->pageTitleHTML);

        self::assertEquals($message, $queueRow->message);

        self::assertEquals($configurationKey, $queueRow->configurationKey);

        self::assertEquals($parameterConfig, $queueRow->parameterConfig);

        self::assertEquals($valueExpanded, $queueRow->valuesExpanded);

        self::assertEquals($urls, $queueRow->urls);

        self::assertEquals($options, $queueRow->options);

        self::assertEquals($parameters, $queueRow->parameters);
    }
}
