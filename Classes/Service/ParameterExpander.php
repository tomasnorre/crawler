<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Expands the parameters of a crawler configuration to individual values.
 *
 * @internal
 */
class ParameterExpander
{
    private const MAX_RANGE_VALUES = 1000;

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
    public function expand(array $paramArray, int $pid): array
    {
        foreach ($paramArray as $parameter => $parameterValue) {
            $paramArray = $this->expandParameter($paramArray, $parameter, trim((string) $parameterValue), $pid);
        }

        return $paramArray;
    }

    private function expandParameter(array $paramArray, int|string $parameter, string $value, int $pid): array
    {
        // Values not wrapped in square brackets are literal values
        if (!$this->isWrappedInSquareBrackets($value)) {
            $paramArray[$parameter] = [$value];
            return $paramArray;
        }

        $paramArray[$parameter] = [];
        foreach (explode('|', substr($value, 1, -1)) as $part) {
            $paramArray = $this->expandPart($paramArray, $parameter, $part, $pid);
            // Hook for processing own expandParameters place holder
            $paramArray = $this->runExpandParametersHook($paramArray, $parameter, $part, $pid);
        }

        // Make unique set of values and sort array by key:
        $paramArray[$parameter] = array_unique($paramArray[$parameter]);
        ksort($paramArray);

        return $paramArray;
    }

    private function expandPart(array $paramArray, int|string $parameter, string $part, int $pid): array
    {
        // Integer range, fx. 1-34 or -40--30 (reads minus 40 to minus 30)
        if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/', trim($part), $range)) {
            return $this->addValuesInRange($range, $paramArray, $parameter);
        }

        if (str_starts_with(trim($part), '_TABLE:')) {
            $tableOptions = $this->parseTableOptions($part);
            if (!isset($GLOBALS['TCA'][$tableOptions['_TABLE']])) {
                return $paramArray;
            }
            return $this->extractParamsFromCustomTable($tableOptions, $pid, $paramArray, $parameter);
        }

        $paramArray[$parameter][] = $part;
        return $paramArray;
    }

    private function isWrappedInSquareBrackets(string $string): bool
    {
        return str_starts_with($string, '[') && str_ends_with($string, ']');
    }

    /**
     * Parses "_TABLE:pages; _PID:123" into ['_TABLE' => 'pages', '_PID' => '123']
     */
    private function parseTableOptions(string $part): array
    {
        $tableOptions = [];
        foreach (GeneralUtility::trimExplode(';', $part) as $option) {
            [$key, $value] = GeneralUtility::trimExplode(':', $option);
            $tableOptions[$key] = $value;
        }
        return $tableOptions;
    }

    /**
     * Adds the values of the range, from low to high, limited to MAX_RANGE_VALUES values
     *
     * @param array<int, string> $range The preg_match() result with the range limits at index 1 and 2
     */
    private function addValuesInRange(array $range, array $paramArray, int|string $parameter): array
    {
        $start = $range[1];
        $end = $range[2];
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $runAwayBrake = self::MAX_RANGE_VALUES;
        for ($value = $start; $value <= $end; $value++) {
            $paramArray[$parameter][] = $value;
            $runAwayBrake--;
            if ($runAwayBrake <= 0) {
                break;
            }
        }
        return $paramArray;
    }

    private function runExpandParametersHook(array $paramArray, int|string $parameter, string $path, int $pid): array
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] ?? null;
        if (!is_array($hooks)) {
            return $paramArray;
        }

        $_params = [
            'pObj' => &$this,
            'paramArray' => &$paramArray,
            'currentKey' => $parameter,
            'currentValue' => $path,
            'pid' => $pid,
        ];
        foreach ($hooks as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return $paramArray;
    }

    private function extractParamsFromCustomTable(
        array $tableOptions,
        int $pid,
        array $paramArray,
        int|string $parameter
    ): array {
        $table = $tableOptions['_TABLE'];
        $fieldName = ($tableOptions['_FIELD'] ?? '') ?: 'uid';
        if ($fieldName !== 'uid' && !($GLOBALS['TCA'][$table]['columns'][$fieldName] ?? false)) {
            return $paramArray;
        }

        $lookUpPid = isset($tableOptions['_PID']) ? (int) $tableOptions['_PID'] : $pid;
        $recursiveDepth = (int) ($tableOptions['_RECURSIVE'] ?? 0);
        $pidField = trim((string) ($tableOptions['_PIDFIELD'] ?? 'pid'));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select($fieldName)
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    $pidField,
                    $queryBuilder->createNamedParameter(
                        $this->getPidArray($recursiveDepth, $lookUpPid),
                        ArrayParameterType::INTEGER
                    )
                ),
                $tableOptions['_WHERE'] ?? ''
            );

        // TODO: Check if this works as intended!
        foreach (GeneralUtility::trimExplode(',', $tableOptions['_ADDTABLE'] ?? '', true) as $addTable) {
            $queryBuilder->from($addTable);
        }

        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? false;
        if (($tableOptions['_ENABLELANG'] ?? false) && $transOrigPointerField) {
            $queryBuilder->andWhere($queryBuilder->expr()->lte($transOrigPointerField, 0));
        }

        $statement = $queryBuilder->executeQuery();

        $rows = [];
        while ($row = $statement->fetchAssociative()) {
            $rows[$row[$fieldName]] = $row;
        }
        $paramArray[$parameter] = array_merge($paramArray[$parameter], array_keys($rows));

        return $paramArray;
    }

    private function getPidArray(int $recursiveDepth, int $lookUpPid): array
    {
        if ($recursiveDepth <= 0) {
            return [$lookUpPid];
        }
        return GeneralUtility::makeInstance(PageRepository::class)->getPageIdsRecursive([$lookUpPid], $recursiveDepth);
    }
}
