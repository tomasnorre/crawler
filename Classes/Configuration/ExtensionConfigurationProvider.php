<?php

declare(strict_types=1);

namespace AOE\Crawler\Configuration;

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
     */
    public function getExtensionConfiguration(): array
    {
        try {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('crawler');
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            $this->logger->error($e->getMessage());
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $this->logger->error($e->getMessage());
        }
        return [];
    }
}
