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
 * The parameters of a queue entry, stored JSON encoded in the "parameters" field of tx_crawler_queue:
 * the URL to crawl with its frontend user groups and processing instructions, or the queue callback to call.
 *
 * Parameters added by integrators (fx. with BeforeQueueItemAddedEvent) are kept.
 *
 * @internal
 */
final readonly class QueueParameters
{
    /**
     * The parameter holding the class name of the QueueCallbackInterface of a queue entry
     */
    public const CALLBACK_PARAMETER = '_CALLBACKOBJ';

    /**
     * @param non-empty-array<array-key, mixed> $parameters
     */
    private function __construct(
        private array $parameters,
    ) {
    }

    /**
     * @param list<string> $processingInstructions
     * @param array<array-key, mixed>|null $processingInstructionParameters
     */
    public static function forUrl(
        string $url,
        string $feUserGroupList = '',
        array $processingInstructions = [''],
        ?array $processingInstructionParameters = null,
    ): self {
        // The order of the keys is part of parameters_hash, which finds duplicate queue entries
        $parameters = [
            'url' => $url,
        ];
        if ($feUserGroupList !== '') {
            $parameters['feUserGroupList'] = $feUserGroupList;
        }
        $parameters['procInstructions'] = $processingInstructions;
        if ($processingInstructionParameters !== null) {
            $parameters['procInstrParams'] = $processingInstructionParameters;
        }
        return new self($parameters);
    }

    /**
     * @param array<array-key, mixed> $parameters Handed over to QueueCallbackInterface::execute()
     */
    public static function forCallback(string $callbackClassName, array $parameters = []): self
    {
        $parameters[self::CALLBACK_PARAMETER] = $callbackClassName;
        return new self($parameters);
    }

    /**
     * Reads the "parameters" field of a queue entry, null if it holds no parameters
     */
    public static function fromJson(string $json): ?self
    {
        $parameters = json_decode($json, true);
        return is_array($parameters) && $parameters !== [] ? new self($parameters) : null;
    }

    /**
     * The value of the "parameters" field of a queue entry
     *
     * @throws \JsonException if a parameter is not JSON encodable
     */
    public function toJson(): string
    {
        return json_encode($this->parameters, JSON_THROW_ON_ERROR);
    }

    /**
     * All parameters, including the ones added by integrators
     *
     * @return non-empty-array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->parameters;
    }

    public function getUrl(): string
    {
        return $this->getString('url');
    }

    /**
     * The frontend user groups to crawl the URL with, comma separated
     */
    public function getFeUserGroupList(): string
    {
        return $this->getString('feUserGroupList');
    }

    /**
     * @return list<string>
     */
    public function getProcessingInstructions(): array
    {
        $processingInstructions = $this->parameters['procInstructions'] ?? [];
        if (!is_array($processingInstructions)) {
            return [];
        }
        return array_values(array_map(strval(...), array_filter($processingInstructions, is_scalar(...))));
    }

    public function isCallback(): bool
    {
        return isset($this->parameters[self::CALLBACK_PARAMETER]);
    }

    public function getCallbackClassName(): string
    {
        return $this->getString(self::CALLBACK_PARAMETER);
    }

    /**
     * The parameters handed over to QueueCallbackInterface::execute()
     *
     * @return array<array-key, mixed>
     */
    public function getCallbackParameters(): array
    {
        $parameters = $this->parameters;
        unset($parameters[self::CALLBACK_PARAMETER]);
        return $parameters;
    }

    private function getString(string $key): string
    {
        $value = $this->parameters[$key] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }
}
