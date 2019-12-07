<?php

declare(strict_types=1);

namespace AOE\Crawler\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionConfigurationProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Return full extension configuration array.
     *
     * @return array $extensionConfiguration
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
