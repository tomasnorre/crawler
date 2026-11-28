<?php

declare(strict_types=1);

namespace AOE\Crawler\Value;

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

/**
 * A record of tx_crawler_queue
 *
 * @internal
 */
final readonly class QueueEntry
{
    /**
     * @param int $qid The uid of the record, 0 if it is not stored yet
     * @param string $parameters The JSON encoded parameters, see QueueParameters
     * @param int $scheduled Unix time the entry is scheduled to
     * @param int $execTime Unix time the entry was crawled, 0 if it is not crawled yet
     * @param string $resultData The JSON encoded crawl result, see CrawlResult
     * @param string $configuration The key of the crawler configuration the entry was created from
     */
    public function __construct(
        public int $qid = 0,
        public int $pageId = 0,
        public string $parameters = '',
        public string $parametersHash = '',
        public string $configurationHash = '',
        public int $scheduled = 0,
        public int $execTime = 0,
        public int $setId = 0,
        public string $resultData = '',
        public string $configuration = '',
    ) {
    }

    /**
     * Creates the entry from a database row, missing or invalid fields get their default
     *
     * @param array<array-key, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            qid: self::getInt($row, 'qid'),
            pageId: self::getInt($row, 'page_id'),
            parameters: self::getString($row, 'parameters'),
            parametersHash: self::getString($row, 'parameters_hash'),
            configurationHash: self::getString($row, 'configuration_hash'),
            scheduled: self::getInt($row, 'scheduled'),
            execTime: self::getInt($row, 'exec_time'),
            setId: self::getInt($row, 'set_id'),
            resultData: self::getString($row, 'result_data'),
            configuration: self::getString($row, 'configuration'),
        );
    }

    /**
     * The fields to insert the entry into tx_crawler_queue with, without the qid
     *
     * @return array{page_id: int, parameters: string, parameters_hash: string, configuration_hash: string, scheduled: int, exec_time: int, set_id: int, result_data: string, configuration: string}
     */
    public function toFieldArray(): array
    {
        return [
            'page_id' => $this->pageId,
            'parameters' => $this->parameters,
            'parameters_hash' => $this->parametersHash,
            'configuration_hash' => $this->configurationHash,
            'scheduled' => $this->scheduled,
            'exec_time' => $this->execTime,
            'set_id' => $this->setId,
            'result_data' => $this->resultData,
            'configuration' => $this->configuration,
        ];
    }

    /**
     * The database row of the entry, with the qid
     *
     * @return array<string, int|string>
     */
    public function toRow(): array
    {
        return [
            'qid' => $this->qid,
        ] + $this->toFieldArray();
    }

    /**
     * The decoded parameters, null if the entry has no valid parameters
     */
    public function getQueueParameters(): ?QueueParameters
    {
        return QueueParameters::fromJson($this->parameters);
    }

    /**
     * The hash the crawled page authenticates the crawler request with, see FrontendUserAuthenticator
     */
    public function getCrawlerHash(string $encryptionKey): string
    {
        return md5($this->qid . '|' . $this->setId . '|' . $encryptionKey);
    }

    /**
     * The value of the X-T3CRAWLER header of the crawler request: the qid and the crawler hash
     */
    public function getCrawlerId(string $encryptionKey): string
    {
        return $this->qid . ':' . $this->getCrawlerHash($encryptionKey);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private static function getInt(array $row, string $field): int
    {
        $value = $row[$field] ?? 0;
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private static function getString(array $row, string $field): string
    {
        $value = $row[$field] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }
}
