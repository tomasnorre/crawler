<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class ConfigurationService
{
    /**
     * @var UrlService
     */
    private $urlService;

    /**
     * @var bool
     */
    private $MP = false;

    public function __construct()
    {
        $this->urlService = GeneralUtility::makeInstance(UrlService::class);
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

    public function getConfigurationFromPageTS(array $pageTSConfig, int $pageId, array $res): array
    {
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

                // recognize MP value
                if (! $this->MP) {
                    $res[$key]['URLs'] = $this->urlService->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId]);
                } else {
                    $res[$key]['URLs'] = $this->urlService->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId . '&MP=' . $this->MP]);
                }
            }
        }
        return $res;
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
     *
     * TODO: Write Functional Tests
     */
    public function expandParameters(array $paramArray, int $pid): array
    {
        // Traverse parameter names:
        foreach ($paramArray as $p => $v) {
            $v = trim($v);

            // If value is encapsulated in square brackets it means there are some ranges of values to find, otherwise the value is literal
            if (strpos($v, '[') === 0 && substr($v, -1) === ']') {
                // So, find the value inside brackets and reset the paramArray value as an array.
                $v = substr($v, 1, -1);
                $paramArray[$p] = [];

                // Explode parts and traverse them:
                $parts = explode('|', $v);
                foreach ($parts as $pV) {

                    // Look for integer range: (fx. 1-34 or -40--30 // reads minus 40 to minus 30)
                    if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/', trim($pV), $reg)) {
                        $reg = $this->swapIfFirstIsLargerThanSecond($reg);

                        // Traverse range, add values:
                        // Limit to size of range!
                        $runAwayBrake = 1000;
                        for ($a = $reg[1]; $a <= $reg[2]; $a++) {
                            $paramArray[$p][] = $a;
                            $runAwayBrake--;
                            if ($runAwayBrake <= 0) {
                                break;
                            }
                        }
                    } elseif (strpos(trim($pV), '_TABLE:') === 0) {

                        // Parse parameters:
                        $subparts = GeneralUtility::trimExplode(';', $pV);
                        $subpartParams = [];
                        foreach ($subparts as $spV) {
                            [$pKey, $pVal] = GeneralUtility::trimExplode(':', $spV);
                            $subpartParams[$pKey] = $pVal;
                        }

                        // Table exists:
                        if (isset($GLOBALS['TCA'][$subpartParams['_TABLE']])) {
                            $lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : intval($pid);
                            $recursiveDepth = isset($subpartParams['_RECURSIVE']) ? intval($subpartParams['_RECURSIVE']) : 0;
                            $pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
                            $where = $subpartParams['_WHERE'] ?? '';
                            $addTable = $subpartParams['_ADDTABLE'] ?? '';

                            $fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
                            if ($fieldName === 'uid' || $GLOBALS['TCA'][$subpartParams['_TABLE']]['columns'][$fieldName]) {
                                $queryBuilder = $this->getQueryBuilder($subpartParams['_TABLE']);

                                if ($recursiveDepth > 0) {
                                    /** @var QueryGenerator $queryGenerator */
                                    $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
                                    $pidList = $queryGenerator->getTreeList($lookUpPid, $recursiveDepth, 0, 1);
                                    $pidArray = GeneralUtility::intExplode(',', $pidList);
                                } else {
                                    $pidArray = [(string) $lookUpPid];
                                }

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
                                    $paramArray[$p] = array_merge($paramArray[$p], array_keys($rows));
                                }
                            }
                        }
                    } else {
                        // Just add value:
                        $paramArray[$p][] = $pV;
                    }
                    // Hook for processing own expandParameters place holder
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'])) {
                        $_params = [
                            'pObj' => &$this,
                            'paramArray' => &$paramArray,
                            'currentKey' => $p,
                            'currentValue' => $pV,
                            'pid' => $pid,
                        ];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] as $_funcRef) {
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                }

                // Make unique set of values and sort array by key:
                $paramArray[$p] = array_unique($paramArray[$p]);
                ksort($paramArray);
            } else {
                // Set the literal value as only value in array:
                $paramArray[$p] = [$v];
            }
        }

        return $paramArray;
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
}
