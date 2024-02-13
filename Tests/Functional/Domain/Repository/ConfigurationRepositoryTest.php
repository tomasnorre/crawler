<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Domain\Repository;

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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationRepositoryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    private const PAGE_WITHOUT_CONFIGURATIONS = 11;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Domain\Repository\ConfigurationRepository $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_crawler_configuration.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/pages.xml');
    }

    /**
     * @test
     */
    public function getCrawlerConfigurationRecordsFromRootLineReturnsEmptyArray(): void
    {
        self::assertEmpty(
            $this->subject->getCrawlerConfigurationRecordsFromRootLine(self::PAGE_WITHOUT_CONFIGURATIONS)
        );
    }

    /**
     * @test
     */
    public function getCrawlerConfigurationRecordsFromRootLineReturnsObjects(): void
    {
        $configurations = $this->subject->getCrawlerConfigurationRecordsFromRootLine(5);

        self::assertCount(4, $configurations);

        foreach ($configurations as $configuration) {
            self::assertContains((int) $configuration['uid'], [1, 5, 6, 8]);
        }
    }
}
