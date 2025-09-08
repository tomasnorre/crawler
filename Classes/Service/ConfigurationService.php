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
use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal since v9.2.5
 */
class ConfigurationService
{
    /**
     * @var BackendUserAuthentication|null
     */
    private $backendUser;
    private readonly array $extensionSettings;

    public function __construct(
        private readonly UrlService $urlService,
        private readonly ConfigurationRepository $configurationRepository
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
        $defaultCompileUrls = 10_000;
        $maxUrlsToCompile = MathUtility::forceIntegerInRange(
            $this->extensionSettings['maxCompileUrls'] ?? $defaultCompileUrls,
            1,
            1_000_000_000,
            $defaultCompileUrls
        );
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
            $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $subCfg['pidsOnly'] ?? '', true));

            // process configuration if it is not page-specific or if the specific page is the current page:
            // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
            if (!strcmp((string) ($subCfg['pidsOnly'] ?? ''), '') || GeneralUtility::inList(
                $pidOnlyList,
                strval($pageId)
            )) {
                // Explode, process etc.:
                $res[$key] = [];
                $res[$key]['subCfg'] = $subCfg;
                $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($crawlerCfg[$key]);
                $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                $res[$key]['origin'] = 'pagets';

                $url = '?id=' . $pageId;
                $url .= $mountPoint !== '' ? '&MP=' . $mountPoint : '';
                $res[$key]['URLs'] = $this->urlService->compileUrls(
                    $res[$key]['paramExpanded'],
                    [$url],
                    $maxUrlsToCompile
                );
            }
        }
        return $res;
    }

    public function getConfigurationFromDatabase(int $pageId, array $res): array
    {
        $maxUrlsToCompile = MathUtility::forceIntegerInRange(
            $this->extensionSettings['maxCompileUrls'],
            1,
            1_000_000_000,
            10000
        );

        $crawlerConfigurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine($pageId);
        foreach ($crawlerConfigurations as $configurationRecord) {
            // check access to the configuration record
            if (empty($configurationRecord['begroups']) || $this->getBackendUser()->isAdmin() || UserService::hasGroupAccess(
                $this->getBackendUser()->user['usergroup_cached_list'],
                $configurationRecord['begroups']
            )) {
                $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $configurationRecord['pidsonly'], true));

                // process configuration if it is not page-specific or if the specific page is the current page:
                // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
                if (!strcmp((string) $configurationRecord['pidsonly'], '') || GeneralUtility::inList(
                    $pidOnlyList,
                    strval($pageId)
                )) {
                    $key = $configurationRecord['name'];

                    // don't overwrite previously defined paramSets
                    if (!isset($res[$key])) {
                        /** @var TypoScriptStringFactory $typoScriptStringFactory */
                        $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
                        $typoScriptTree = $typoScriptStringFactory->parseFromString(
                            $configurationRecord['processing_instruction_parameters_ts'],
                            new AstBuilder(new NoopEventDispatcher())
                        );

                        $subCfg = [
                            'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
                            'procInstrParams.' => $typoScriptTree->toArray(),
                            'baseUrl' => $configurationRecord['base_url'],
                            'force_ssl' => (int) $configurationRecord['force_ssl'],
                            'userGroups' => $configurationRecord['fegroups'],
                            'exclude' => $configurationRecord['exclude'],
                            'key' => $key,
                        ];

                        if (!in_array($pageId, $this->expandExcludeString($subCfg['exclude']), true)) {
                            $res[$key] = [];
                            $res[$key]['subCfg'] = $subCfg;
                            $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array(
                                $configurationRecord['configuration']
                            );
                            $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                            $res[$key]['URLs'] = $this->urlService->compileUrls(
                                $res[$key]['paramExpanded'],
                                ['?id=' . $pageId],
                                $maxUrlsToCompile
                            );
                            $res[$key]['origin'] = 'tx_crawler_configuration_' . $configurationRecord['uid'];
                        }
                    }
                }
            }
        }
        return $res;
    }

    public function expandExcludeString(string $excludeString): array
    {
        // internal static caches;
        static $expandedExcludeStringCache;
        static $treeCache = [];

        if (!empty($expandedExcludeStringCache[$excludeString])) {
            return $expandedExcludeStringCache[$excludeString];
        }

        $pidList = [];

        if (!empty($excludeString)) {
            /** @var PageTreeView $tree */
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

            $excludeParts = GeneralUtility::trimExplode(',', $excludeString);

            foreach ($excludeParts as $excludePart) {
                $explodedExcludePart = GeneralUtility::trimExplode('+', $excludePart);
                $pid = isset($explodedExcludePart[0]) ? (int) $explodedExcludePart[0] : 0;
                $depth = isset($explodedExcludePart[1]) ? (int) $explodedExcludePart[1] : null;

                // default is "page only" = "depth=0"
                if (empty($depth)) {
                    $depth = (str_contains($excludePart, '+')) ? 99 : 0;
                }

                $pidList[] = $pid;
                if ($depth > 0) {
                    $pidList = $this->expandPidList($treeCache, $pid, $depth, $tree, $pidList);
                }
            }
        }

        $expandedExcludeStringCache[$excludeString] = array_unique($pidList);

        return $expandedExcludeStringCache[$excludeString];
    }

    /**
     * Expands the parameters configuration into individual values.
     *
     * Syntax of values:
     * - Literal values are taken as-is unless wrapped in [ ].
     * - If wrapped in [ ]:
     *   - Parts are separated by "|" and expanded individually.
     *   - Supported parts:
     *       - "x-y"       → Integer range (inclusive, max 1000 values)
     *       - "_TABLE:..." → Lookup values from a TCA table
     *       - Otherwise   → Literal value
     */
    private function expandParameters(array $paramArray, int $pid): array
    {
        /** @var array<string, string|int|float|null> $paramArray */
        foreach ($paramArray as $parameter => $rawValue) {
            $value = trim((string) $rawValue);

            if ($this->isWrappedInSquareBrackets($value)) {
                /** @var array<int, string|int> $expanded */
                $expanded = $this->expandBracketedValue($value, $pid, $parameter, $paramArray);
                $paramArray[$parameter] = $expanded;
            } else {
                /** @var array<int, string> $paramArray */
                $paramArray[$parameter] = [$value];
            }
        }

        /** @var array<string, array<int, string|int>> $paramArray */
        return $paramArray;
    }

    /**
     * Expands a value wrapped in [ ] into an array of individual values.
     *
     * @param array<string, mixed>             $paramArray
     * @return array<int, string|int>
     */
    private function expandBracketedValue(string $value, int $pid, string $parameter, array $paramArray): array
    {
        $expandedValues = [];
        $innerValue = substr($value, 1, -1);
        $parts = explode('|', $innerValue);

        foreach ($parts as $part) {
            $part = trim($part);

            if ($this->isIntegerRange($part)) {
                $expandedValues = array_merge($expandedValues, $this->expandIntegerRange($part));
            } elseif (str_starts_with($part, '_TABLE:')) {
                $expandedValues = array_merge(
                    $expandedValues,
                    $this->expandTableLookup($part, $pid, $parameter, $paramArray)
                );
            } else {
                $expandedValues[] = $part;
            }

            // Allow custom hooks to modify the expanded values
            $paramArray = $this->runExpandParametersHook($paramArray, $parameter, $part, $pid);
        }

        return array_values(array_unique($expandedValues));
    }

    /**
     * Checks whether a part is an integer range like "1-34" or "-40--30".
     */
    private function isIntegerRange(string $part): bool
    {
        return (bool) preg_match('/^(-?\d+)\s*-\s*(-?\d+)$/', $part);
    }

    /**
     * Expands an integer range string into an array of integers.
     *
     * @return array<int, int>
     */
    private function expandIntegerRange(string $part): array
    {
        preg_match('/^(-?\d+)\s*-\s*(-?\d+)$/', $part, $matches);

        [, $start, $end] = $matches;
        $start = (int) $start;
        $end = (int) $end;

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $range = range($start, $end);

        // Safety limit to prevent runaway ranges
        return count($range) > 1000 ? array_slice($range, 0, 1000) : $range;
    }

    /**
     * Expands a _TABLE lookup definition into values from the TCA table.
     *
     * @param array<string, mixed>             $paramArray
     * @return array<int, string|int>
     */
    private function expandTableLookup(string $definition, int $pid, string $parameter, array $paramArray): array
    {
        $subparts = GeneralUtility::trimExplode(';', $definition);
        $options = [];

        foreach ($subparts as $subpart) {
            [$key, $value] = GeneralUtility::trimExplode(':', $subpart);
            $options[$key] = $value;
        }

        if (!isset($GLOBALS['TCA'][$options['_TABLE']])) {
            return []; // invalid table
        }

        return $this->extractParamsFromCustomTable($options, $pid, $paramArray, $parameter);
    }

    private function isWrappedInSquareBrackets(string $string): bool
    {
        return str_starts_with($string, '[') && str_ends_with($string, ']');
    }

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser()
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        if ($this->backendUser === null) {
            $this->backendUser = $GLOBALS['BE_USER'];
        }
        return $this->backendUser;
    }

    private function getQueryBuilder(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    /**
     * @psalm-param array-key $parameter
     */
    private function runExpandParametersHook(array $paramArray, int|string $parameter, string $path, int $pid): array
    {
        if (is_array(
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] ?? null
        )) {
            $_params = [
                'pObj' => &$this,
                'paramArray' => &$paramArray,
                'currentKey' => $parameter,
                'currentValue' => $path,
                'pid' => $pid,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $paramArray;
    }

    private function getPidArray(int $recursiveDepth, int $lookUpPid): array
    {
        if ($recursiveDepth > 0) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $pidArray = $pageRepository->getPageIdsRecursive([$lookUpPid], $recursiveDepth);
        } else {
            $pidArray = [$lookUpPid];
        }
        return $pidArray;
    }

    private function expandPidList(array $treeCache, int $pid, int $depth, PageTreeView $tree, array $pidList): array
    {
        if (empty($treeCache[$pid][$depth])) {
            $tree->reset();
            $tree->getTree($pid, $depth);
            $treeCache[$pid][$depth] = $tree->tree;
        }

        foreach ($treeCache[$pid][$depth] as $data) {
            $pidList[] = (int) $data['row']['uid'];
        }
        return $pidList;
    }

    private function extractParamsFromCustomTable(
        array $subpartParams,
        int $pid,
        array $paramArray,
        int|string $parameter
    ): array {
        $lookUpPid = isset($subpartParams['_PID']) ? (int) $subpartParams['_PID'] : $pid;
        $recursiveDepth = isset($subpartParams['_RECURSIVE']) ? (int) $subpartParams['_RECURSIVE'] : 0;
        $pidField = isset($subpartParams['_PIDFIELD']) ? trim((string) $subpartParams['_PIDFIELD']) : 'pid';
        $where = $subpartParams['_WHERE'] ?? '';
        $addTable = $subpartParams['_ADDTABLE'] ?? '';

        $fieldName = ($subpartParams['_FIELD'] ?? '') ?: 'uid';
        if ($fieldName === 'uid' || $GLOBALS['TCA'][$subpartParams['_TABLE']]['columns'][$fieldName]) {
            $queryBuilder = $this->getQueryBuilder($subpartParams['_TABLE']);
            $pidArray = $this->getPidArray($recursiveDepth, $lookUpPid);

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder
                ->select($fieldName)
                ->from($subpartParams['_TABLE'])
                ->where(
                    $queryBuilder->expr()->in(
                        $pidField,
                        $queryBuilder->createNamedParameter($pidArray, ArrayParameterType::INTEGER)
                    ),
                    $where
                );

            if (!empty($addTable)) {
                // TODO: Check if this works as intended!
                $addTables = GeneralUtility::trimExplode(',', $addTable, true);
                foreach ($addTables as $table) {
                    $queryBuilder->from($table);
                }
            }
            $transOrigPointerField = $GLOBALS['TCA'][$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'] ?? false;

            if (($subpartParams['_ENABLELANG'] ?? false) && $transOrigPointerField) {
                $queryBuilder->andWhere($queryBuilder->expr()->lte($transOrigPointerField, 0));
            }

            $statement = $queryBuilder->executeQuery();

            $rows = [];
            while ($row = $statement->fetchAssociative()) {
                $rows[$row[$fieldName]] = $row;
            }

            if (is_array($rows)) {
                $paramArray[$parameter] = array_merge($paramArray[$parameter], array_keys($rows));
            }
        }
        return $paramArray;
    }
}
