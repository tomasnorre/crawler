<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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
use Doctrine\DBAL\Connection;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * @internal since v9.2.5
 */
class ConfigurationService
{
    /**
     * @var BackendUserAuthentication|null
     */
    private $backendUser;

    /**
     * @var UrlService
     */
    private $urlService;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var array
     */
    private $extensionSettings;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->urlService = GeneralUtility::makeInstance(UrlService::class);
        $this->configurationRepository = $objectManager->get(ConfigurationRepository::class);
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
    }

    public static function removeDisallowedConfigurations(array $allowedConfigurations, array $configurations): array
    {
        if (! empty($allowedConfigurations)) {
            // 	remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (! in_array($confKey, $allowedConfigurations, true)) {
                    unset($configurations[$confKey]);
                }
            }
        }
        return $configurations;
    }

    public function getConfigurationFromPageTS(array $pageTSConfig, int $pageId, array $res, string $mountPoint = ''): array
    {
        $maxUrlsToCompile = MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000);
        $crawlerCfg = $pageTSConfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($crawlerCfg as $key => $values) {
            if (! is_array($values)) {
                continue;
            }
            $key = str_replace('.', '', $key);
            // Sub configuration for a single configuration string:
            $subCfg = (array) $crawlerCfg[$key . '.'];
            $subCfg['key'] = $key;

            if (strcmp($subCfg['procInstrFilter'] ?? '', '')) {
                $subCfg['procInstrFilter'] = implode(',', GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']));
            }
            $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $subCfg['pidsOnly'], true));

            // process configuration if it is not page-specific or if the specific page is the current page:
            // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
            if (! strcmp((string) $subCfg['pidsOnly'], '') || GeneralUtility::inList($pidOnlyList, strval($pageId))) {

                // Explode, process etc.:
                $res[$key] = [];
                $res[$key]['subCfg'] = $subCfg;
                $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($crawlerCfg[$key]);
                $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                $res[$key]['origin'] = 'pagets';

                $url = '?id=' . $pageId;
                $url .= is_string($mountPoint) ? '&MP=' . $mountPoint : '';
                $res[$key]['URLs'] = $this->getUrlService()->compileUrls($res[$key]['paramExpanded'], [$url], $maxUrlsToCompile);
            }
        }
        return $res;
    }

    public function getConfigurationFromDatabase(int $pageId, array $res): array
    {
        $maxUrlsToCompile = MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000);

        $crawlerConfigurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine($pageId);
        foreach ($crawlerConfigurations as $configurationRecord) {

            // check access to the configuration record
            if (empty($configurationRecord['begroups']) || $this->getBackendUser()->isAdmin() || UserService::hasGroupAccess($this->getBackendUser()->user['usergroup_cached_list'], $configurationRecord['begroups'])) {
                $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $configurationRecord['pidsonly'], true));

                // process configuration if it is not page-specific or if the specific page is the current page:
                // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
                if (! strcmp($configurationRecord['pidsonly'], '') || GeneralUtility::inList($pidOnlyList, strval($pageId))) {
                    $key = $configurationRecord['name'];

                    // don't overwrite previously defined paramSets
                    if (! isset($res[$key])) {

                        /* @var $TSparserObject TypoScriptParser */
                        $TSparserObject = GeneralUtility::makeInstance(TypoScriptParser::class);
                        $TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

                        $subCfg = [
                            'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
                            'procInstrParams.' => $TSparserObject->setup,
                            'baseUrl' => $configurationRecord['base_url'],
                            'force_ssl' => (int) $configurationRecord['force_ssl'],
                            'userGroups' => $configurationRecord['fegroups'],
                            'exclude' => $configurationRecord['exclude'],
                            'key' => $key,
                        ];

                        if (! in_array($pageId, $this->expandExcludeString($subCfg['exclude']), true)) {
                            $res[$key] = [];
                            $res[$key]['subCfg'] = $subCfg;
                            $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($configurationRecord['configuration']);
                            $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                            $res[$key]['URLs'] = $this->getUrlService()->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId], $maxUrlsToCompile);
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

        if (empty($expandedExcludeStringCache[$excludeString])) {
            $pidList = [];

            if (! empty($excludeString)) {
                /** @var PageTreeView $tree */
                $tree = GeneralUtility::makeInstance(PageTreeView::class);
                $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

                $excludeParts = GeneralUtility::trimExplode(',', $excludeString);

                foreach ($excludeParts as $excludePart) {
                    [$pid, $depth] = GeneralUtility::trimExplode('+', $excludePart);

                    // default is "page only" = "depth=0"
                    if (empty($depth)) {
                        $depth = (strpos($excludePart, '+') !== false) ? 99 : 0;
                    }

                    $pidList[] = (int) $pid;
                    if ($depth > 0) {
                        $pidList = $this->expandPidList($treeCache, $pid, $depth, $tree, $pidList);
                    }
                }
            }

            $expandedExcludeStringCache[$excludeString] = array_unique($pidList);
        }

        return $expandedExcludeStringCache[$excludeString];
    }

    protected function getUrlService(): UrlService
    {
        $this->urlService = $this->urlService ?? GeneralUtility::makeInstance(UrlService::class);
        return $this->urlService;
    }

    /**
     * Will expand the parameters configuration to individual values. This follows a certain syntax of the value of each parameter.
     * Syntax of values:
     * - Basically: If the value is wrapped in [...] it will be expanded according to the following syntax, otherwise the value is taken literally
     * - Configuration is splitted by "|" and the parts are processed individually and finally added together
     * - For each configuration part:
     *         - "[int]-[int]" = Integer range, will be expanded to all values in between, values included, starting from low to high (max. 1000). Example "1-34" or "-40--30"
     *         - "_TABLE:[TCA table name];[_PID:[optional page id, default is current page]];[_ENABLELANG:1]" = Look up of table records from PID, filtering out deleted records. Example "_TABLE:tt_content; _PID:123"
     *        _ENABLELANG:1 picks only original records without their language overlays
     *         - Default: Literal value
     */
    private function expandParameters(array $paramArray, int $pid): array
    {
        // Traverse parameter names:
        foreach ($paramArray as $parameter => $parameterValue) {
            $parameterValue = trim($parameterValue);

            // If value is encapsulated in square brackets it means there are some ranges of values to find, otherwise the value is literal
            if ($this->isWrappedInSquareBrackets($parameterValue)) {
                // So, find the value inside brackets and reset the paramArray value as an array.
                $parameterValue = substr($parameterValue, 1, -1);
                $paramArray[$parameter] = [];

                // Explode parts and traverse them:
                $parts = explode('|', $parameterValue);
                foreach ($parts as $part) {

                    // Look for integer range: (fx. 1-34 or -40--30 // reads minus 40 to minus 30)
                    if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/', trim($part), $reg)) {
                        $reg = $this->swapIfFirstIsLargerThanSecond($reg);
                        $paramArray = $this->addValuesInRange($reg, $paramArray, $parameter);
                    } elseif (strpos(trim($part), '_TABLE:') === 0) {

                        // Parse parameters:
                        $subparts = GeneralUtility::trimExplode(';', $part);
                        $subpartParams = [];
                        foreach ($subparts as $spV) {
                            [$pKey, $pVal] = GeneralUtility::trimExplode(':', $spV);
                            $subpartParams[$pKey] = $pVal;
                        }

                        // Table exists:
                        if (isset($GLOBALS['TCA'][$subpartParams['_TABLE']])) {
                            $lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : $pid;
                            $recursiveDepth = isset($subpartParams['_RECURSIVE']) ? intval($subpartParams['_RECURSIVE']) : 0;
                            $pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
                            $where = $subpartParams['_WHERE'] ?? '';
                            $addTable = $subpartParams['_ADDTABLE'] ?? '';

                            $fieldName = $subpartParams['_FIELD'] ?: 'uid';
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
                                        $queryBuilder->expr()->in($pidField, $queryBuilder->createNamedParameter($pidArray, Connection::PARAM_INT_ARRAY)),
                                        $where
                                    );

                                if (! empty($addTable)) {
                                    // TODO: Check if this works as intended!
                                    $queryBuilder->add('from', $addTable);
                                }
                                $transOrigPointerField = $GLOBALS['TCA'][$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'];

                                if ($subpartParams['_ENABLELANG'] && $transOrigPointerField) {
                                    $queryBuilder->andWhere(
                                        $queryBuilder->expr()->lte(
                                            $transOrigPointerField,
                                            0
                                        )
                                    );
                                }

                                $statement = $queryBuilder->execute();

                                $rows = [];
                                while ($row = $statement->fetch()) {
                                    $rows[$row[$fieldName]] = $row;
                                }

                                if (is_array($rows)) {
                                    $paramArray[$parameter] = array_merge($paramArray[$parameter], array_keys($rows));
                                }
                            }
                        }
                    } else {
                        // Just add value:
                        $paramArray[$parameter][] = $part;
                    }
                    // Hook for processing own expandParameters place holder
                    $paramArray = $this->runExpandParametersHook($paramArray, $parameter, $part, $pid);
                }

                // Make unique set of values and sort array by key:
                $paramArray[$parameter] = array_unique($paramArray[$parameter]);
                ksort($paramArray);
            } else {
                // Set the literal value as only value in array:
                $paramArray[$parameter] = [$parameterValue];
            }
        }

        return $paramArray;
    }

    private function isWrappedInSquareBrackets(string $string): bool
    {
        return (strpos($string, '[') === 0 && substr($string, -1) === ']');
    }

    private function swapIfFirstIsLargerThanSecond(array $reg): array
    {
        // Swap if first is larger than last:
        if ($reg[1] > $reg[2]) {
            $temp = $reg[2];
            $reg[2] = $reg[1];
            $reg[1] = $temp;
        }

        return $reg;
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

    /**
     * Get querybuilder for given table
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(string $table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    /**
     * @param $parameter
     * @param $path
     */
    private function runExpandParametersHook(array $paramArray, $parameter, $path, int $pid): array
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'])) {
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
            /** @var QueryGenerator $queryGenerator */
            $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
            $pidList = $queryGenerator->getTreeList($lookUpPid, $recursiveDepth, 0, 1);
            $pidArray = GeneralUtility::intExplode(',', $pidList);
        } else {
            $pidArray = [$lookUpPid];
        }
        return $pidArray;
    }

    /**
     * @param $parameter
     *
     * Traverse range, add values:
     * Limit to size of range!
     */
    private function addValuesInRange(array $reg, array $paramArray, $parameter): array
    {
        $runAwayBrake = 1000;
        for ($a = $reg[1]; $a <= $reg[2]; $a++) {
            $paramArray[$parameter][] = $a;
            $runAwayBrake--;
            if ($runAwayBrake <= 0) {
                break;
            }
        }
        return $paramArray;
    }

    /**
     * @param $depth
     */
    private function expandPidList(array $treeCache, string $pid, $depth, PageTreeView $tree, array $pidList): array
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
}
