<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Configuration;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class ExtensionConfigurationProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Return full extension configuration array.
     * @return mixed|array
     */
    public function getExtensionConfiguration()
    {
        try {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('crawler');
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            $this->logger->error($e->getMessage());
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
