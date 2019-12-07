<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Domain\Repository;

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ConfigurationRepositoryTest
 */
class ConfigurationRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var ConfigurationRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(ConfigurationRepository::class);
        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_crawler_configuration.xml');
    }

    /**
     * @test
     */
    public function getCrawlerConfigurationRecords(): void
    {
        self::assertSame(
            3,
            count($this->subject->getCrawlerConfigurationRecords())
        );
    }
}
