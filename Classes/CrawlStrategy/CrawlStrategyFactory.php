<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * @internal since v12.0.0
 */
final readonly class CrawlStrategyFactory
{
    public function __construct(
        private ExtensionConfigurationProvider $configurationProvider,
        #[AutowireLocator(services: [GuzzleExecutionStrategy::class, FrontendSubRequestExecutionStrategy::class])]
        private ContainerInterface $strategies,
    ) {
    }

    public function create(): CrawlStrategyInterface
    {
        $extensionSettings = $this->configurationProvider->getExtensionConfiguration();
        $strategy = ($extensionSettings['makeDirectRequests'] ?? false)
            ? FrontendSubRequestExecutionStrategy::class
            : GuzzleExecutionStrategy::class;

        /** @var CrawlStrategyInterface */
        return $this->strategies->get($strategy);
    }
}
