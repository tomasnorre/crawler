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
 * The result of crawling a queue entry: the crawler data the crawled page reported
 * (fx. "errorlog", "log", "vars" and "parameters"), or the return value of a queue callback.
 *
 * Stored JSON encoded in the "result_data" field of tx_crawler_queue.
 *
 * @psalm-api Handed over to integrators by AfterUrlCrawledEvent
 */
final readonly class CrawlResult
{
    public const STATUS_OK = 'OK';
    public const STATUS_NO_INFO = 'Error - no info, sorry!';

    private function __construct(
        private mixed $content,
    ) {
    }

    /**
     * @param mixed $content The crawler data of the page or the return value of a queue callback, must be JSON encodable
     */
    public static function fromContent(mixed $content): self
    {
        return new self($content);
    }

    /**
     * @param array<array-key, mixed> $vars
     */
    public static function error(string $message, array $vars = []): self
    {
        $content = [
            'errorlog' => [$message],
        ];
        if ($vars !== []) {
            $content['vars'] = $vars;
        }
        return new self($content);
    }

    /**
     * Reads the result from the "result_data" field of a queue entry, null if the entry is not crawled yet
     */
    public static function fromResultData(string $resultData): ?self
    {
        $resultData = json_decode($resultData, true);
        if (empty($resultData)) {
            return null;
        }
        $content = is_array($resultData) ? ($resultData['content'] ?? null) : null;
        return new self(is_scalar($content) ? json_decode((string) $content, true) : null);
    }

    /**
     * The value of the "result_data" field of a queue entry
     */
    public function toResultData(): string
    {
        return (string) json_encode([
            'content' => json_encode($this->content),
        ]);
    }

    /**
     * The crawler data of the page or the return value of a queue callback
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Whether the content is crawler data (an array), not fx. the scalar return value of a queue callback
     */
    public function hasCrawlerData(): bool
    {
        return is_array($this->content);
    }

    /**
     * @return list<string>
     */
    public function getErrorLog(): array
    {
        return $this->getStringList('errorlog');
    }

    /**
     * @return list<string>
     */
    public function getLog(): array
    {
        return $this->getStringList('log');
    }

    /**
     * The variables of the frontend request, fx. "id", "gr_list" and "no_cache"
     *
     * @return array<array-key, mixed>
     */
    public function getVars(): array
    {
        $vars = $this->getCrawlerData()['vars'] ?? [];
        return is_array($vars) ? $vars : [];
    }

    /**
     * "OK", the error log or "Error - no info, sorry!" if the content is no crawler data
     */
    public function getStatus(): string
    {
        if (!$this->hasCrawlerData()) {
            return self::STATUS_NO_INFO;
        }
        $errorLog = $this->getErrorLog();
        return $errorLog === [] ? self::STATUS_OK : implode(chr(10), $errorLog);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getCrawlerData(): array
    {
        return is_array($this->content) ? $this->content : [];
    }

    /**
     * @return list<string>
     */
    private function getStringList(string $key): array
    {
        $values = $this->getCrawlerData()[$key] ?? [];
        if (!is_array($values)) {
            return [];
        }
        return array_values(array_map(strval(...), array_filter($values, is_scalar(...))));
    }
}
