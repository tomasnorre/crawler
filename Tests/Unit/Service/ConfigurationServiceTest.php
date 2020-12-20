<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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

use AOE\Crawler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider removeDisallowedConfigurationsDataProvider
     */
    public function removeDisallowedConfigurationsReturnsExpectedArray(array $allowed, array $configuration, array $expected): void
    {
        self::assertEquals(
            $expected,
            ConfigurationService::removeDisallowedConfigurations($allowed, $configuration)
        );
    }

    public function removeDisallowedConfigurationsDataProvider(): array
    {
        return [
            'both allowed and configuration is empty' => [
                'allowed' => [],
                'configurations' => [],
                'expected' => [],
            ],
            'allowed is empty' => [
                'allowed' => [],
                'configurations' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
                'expected' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
            ],
            'news is not allowed' => [
                'allowed' => ['default'],
                'configurations' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
                'expected' => ['default' => 'configuration-text'],
            ],
        ];
    }
}
