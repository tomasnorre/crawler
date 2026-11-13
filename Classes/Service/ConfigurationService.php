<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal since v9.2.5
 */
class ConfigurationService
{
    private const DEFAULT_MAX_URLS_TO_COMPILE = 10_000;

    private readonly array $extensionSettings;

    public function __construct(
        private readonly UrlService $urlService,
        private readonly ConfigurationRepository $configurationRepository,
        private readonly GroupResolver $groupResolver,
        private readonly ParameterExpander $parameterExpander,
        private readonly ExcludeStringExpander $excludeStringExpander,
    ) {
        $this->extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();
    }

    public static function removeDisallowedConfigurations(array $allowedConfigurations, array $configurations): array
    {
        if (empty($allowedConfigurations)) {
            return $configurations;
        }
        // 	remove configuration that does not match the current selection
        foreach ($configurations as $confKey => $confArray) {
            if (!in_array($confKey, $allowedConfigurations, true)) {
                unset($configurations[$confKey]);
            }
        }

        return $configurations;
    }

    public function getConfigurationFromPageTS(
        array $pageTSConfig,
        int $pageId,
        array $res,
        string $mountPoint = ''
    ): array {
        $crawlerCfg = $pageTSConfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($crawlerCfg as $key => $values) {
            if (!is_array($values)) {
                continue;
            }
            $key = str_replace('.', '', (string) $key);
            // Sub configuration for a single configuration string:
            $subCfg = (array) $crawlerCfg[$key . '.'];
            $subCfg['key'] = $key;

            if (strcmp($subCfg['procInstrFilter'] ?? '', '')) {
                $subCfg['procInstrFilter'] = implode(',', GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']));
            }

            if (!$this->appliesToPage((string) ($subCfg['pidsOnly'] ?? ''), $pageId)) {
                continue;
            }

            $url = '?id=' . $pageId;
            $url .= $mountPoint !== '' ? '&MP=' . $mountPoint : '';
            $res[$key] = $this->buildConfiguration($subCfg, (string) $crawlerCfg[$key], $pageId, $url, 'pagets');
        }
        return $res;
    }

    public function getConfigurationFromDatabase(int $pageId, array $res): array
    {
        $crawlerConfigurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine($pageId);
        foreach ($crawlerConfigurations as $configurationRecord) {
            $key = $configurationRecord['name'];

            // Previously defined paramSets are not overwritten
            if (isset($res[$key])
                || !$this->hasAccess($configurationRecord)
                || !$this->appliesToPage((string) $configurationRecord['pidsonly'], $pageId)
                || in_array($pageId, $this->expandExcludeString((string) $configurationRecord['exclude']), true)
            ) {
                continue;
            }

            $res[$key] = $this->buildConfiguration(
                $this->buildSubConfigurationFromRecord($configurationRecord),
                (string) $configurationRecord['configuration'],
                $pageId,
                '?id=' . $pageId,
                'tx_crawler_configuration_' . $configurationRecord['uid']
            );
        }
        return $res;
    }

    /**
     * @return array<int, int>
     */
    public function expandExcludeString(string $excludeString): array
    {
        return $this->excludeStringExpander->expand($excludeString);
    }

    /**
     * A configuration applies to all pages, unless "pidsOnly" limits it to some pages
     */
    private function appliesToPage(string $pidsOnly, int $pageId): bool
    {
        if ($pidsOnly === '') {
            return true;
        }
        return in_array((string) $pageId, GeneralUtility::trimExplode(',', $pidsOnly, true), true);
    }

    private function hasAccess(array $configurationRecord): bool
    {
        if (empty($configurationRecord['begroups']) || $this->getBackendUser()->isAdmin()) {
            return true;
        }
        return UserService::hasGroupAccess(
            implode(',', $this->groupResolver->resolveGroupsForUser([$this->getBackendUser()], 'be_groups')),
            $configurationRecord['begroups']
        );
    }

    private function buildSubConfigurationFromRecord(array $configurationRecord): array
    {
        $typoScriptTree = GeneralUtility::makeInstance(TypoScriptStringFactory::class)->parseFromString(
            $configurationRecord['processing_instruction_parameters_ts'],
            new AstBuilder(new NoopEventDispatcher())
        );

        return [
            'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
            'procInstrParams.' => $typoScriptTree->toArray(),
            'baseUrl' => $configurationRecord['base_url'],
            'force_ssl' => (int) $configurationRecord['force_ssl'],
            'userGroups' => $configurationRecord['fegroups'],
            'exclude' => $configurationRecord['exclude'],
            'key' => $configurationRecord['name'],
        ];
    }

    private function buildConfiguration(
        array $subCfg,
        string $parameters,
        int $pageId,
        string $baseUrl,
        string $origin
    ): array {
        $configuration = [];
        $configuration['subCfg'] = $subCfg;
        $configuration['paramParsed'] = GeneralUtility::explodeUrl2Array($parameters);
        $configuration['paramExpanded'] = $this->parameterExpander->expand($configuration['paramParsed'], $pageId);
        $configuration['origin'] = $origin;
        $configuration['URLs'] = $this->urlService->compileUrls(
            $configuration['paramExpanded'],
            [$baseUrl],
            $this->getMaxUrlsToCompile()
        );
        return $configuration;
    }

    private function getMaxUrlsToCompile(): int
    {
        return MathUtility::forceIntegerInRange(
            $this->extensionSettings['maxCompileUrls'] ?? self::DEFAULT_MAX_URLS_TO_COMPILE,
            1,
            1_000_000_000,
            self::DEFAULT_MAX_URLS_TO_COMPILE
        );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        return $GLOBALS['BE_USER'];
    }
}
