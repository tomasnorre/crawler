<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

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

use AOE\Crawler\Queue\QueueCallbackInterface;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Executes the QueueCallbackInterface of a queue entry added with QueueEntryWriter::addCallback().
 *
 * @internal since v12.0.0
 */
class CallbackExecutionStrategy
{
    /**
     * @param ContainerInterface $callbacks The callbacks registered in the dependency injection container,
     *                                      by class name
     */
    public function __construct(
        private readonly ContainerInterface $callbacks,
    ) {
    }

    public function fetchByCallback(string $callbackClassName, array $parameters): mixed
    {
        return $this->getCallback($callbackClassName)->execute($parameters);
    }

    private function getCallback(string $callbackClassName): QueueCallbackInterface
    {
        if ($this->callbacks->has($callbackClassName)) {
            $callback = $this->callbacks->get($callbackClassName);
        } elseif (class_exists($callbackClassName)) {
            // Callbacks of extensions without autoconfiguration
            $callback = GeneralUtility::makeInstance($callbackClassName);
        } else {
            $callback = null;
        }

        if (!$callback instanceof QueueCallbackInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Callback class "%s" must exist and implement %s',
                    $callbackClassName,
                    QueueCallbackInterface::class
                ),
                1763020800
            );
        }
        return $callback;
    }
}
