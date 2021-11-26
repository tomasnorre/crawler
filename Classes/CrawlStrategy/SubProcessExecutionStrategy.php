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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Utility\PhpBinaryUtility;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Executes another process via shell_exec() to include cli/bootstrap.php which in turn
 * includes the index.php for frontend.
 */
class SubProcessExecutionStrategy implements LoggerAwareInterface, CrawlStrategyInterface
{
    use LoggerAwareTrait;

    protected array $extensionSettings;

    public function __construct(?ExtensionConfigurationProvider $configurationProvider = null)
    {
        $configurationProvider = $configurationProvider ?? GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $settings = $configurationProvider->getExtensionConfiguration();
        $this->extensionSettings = is_array($settings) ? $settings : [];
    }

    /**
     * Fetches a URL by calling a shell script.
     *
     * @return array|bool|mixed
     */
    public function fetchUrlContents(UriInterface $url, string $crawlerId)
    {
        $url = (string) $url;
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            $this->logger->debug(
                sprintf('Could not parse_url() for string "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
            return false;
        }

        if (! isset($parsedUrl['scheme']) || ! in_array($parsedUrl['scheme'], ['', 'http', 'https'], true)) {
            $this->logger->debug(
                sprintf('Scheme does not match for url "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
            return false;
        }

        if (! is_array($parsedUrl)) {
            return [];
        }

        $requestHeaders = $this->buildRequestHeaders($parsedUrl, $crawlerId);

        $commandParts = [
            ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php',
            $this->getFrontendBasePath(),
            $url,
            base64_encode(serialize($requestHeaders)),
        ];
        $commandParts = CommandUtility::escapeShellArguments($commandParts);
        $cmd = escapeshellcmd(PhpBinaryUtility::getPhpBinary());
        $cmd .= ' ' . implode(' ', $commandParts);

        $startTime = microtime(true);
        $content = $this->executeShellCommand($cmd);
        $this->logger->info($url . ' ' . (microtime(true) - $startTime));

        if ($content === null) {
            return false;
        }

        return ['content' => $content];
    }

    private function buildRequestHeaders(array $url, string $crawlerId): array
    {
        $reqHeaders = [];
        $reqHeaders[] = 'GET ' . $url['path'] . (isset($url['query']) ? '?' . $url['query'] : '') . ' HTTP/1.0';
        $reqHeaders[] = 'Host: ' . $url['host'];
        $reqHeaders[] = 'Connection: close';
        if (isset($url['user'], $url['pass']) && $url['user'] !== '' && $url['pass'] !== '') {
            $reqHeaders[] = 'Authorization: Basic ' . base64_encode($url['user'] . ':' . $url['pass']);
        }
        $reqHeaders[] = 'X-T3crawler: ' . $crawlerId;
        $reqHeaders[] = 'User-Agent: TYPO3 crawler';
        return $reqHeaders;
    }

    /**
     * Executes a shell command and returns the outputted result.
     *
     * @param string $command Shell command to be executed
     * @return string|null Outputted result of the command execution
     */
    private function executeShellCommand($command)
    {
        return shell_exec($command);
    }

    /**
     * Gets the base path of the website frontend.
     * (e.g. if you call http://mydomain.com/cms/index.php in
     * the browser the base path is "/cms/")
     *
     * @return string Base path of the website frontend
     */
    private function getFrontendBasePath()
    {
        $frontendBasePath = '/';

        // Get the path from the extension settings:
        if (isset($this->extensionSettings['frontendBasePath']) && $this->extensionSettings['frontendBasePath']) {
            $frontendBasePath = $this->extensionSettings['frontendBasePath'];
        // If empty, try to use config.absRefPrefix:
        } elseif (isset($GLOBALS['TSFE']->absRefPrefix) && ! empty($GLOBALS['TSFE']->absRefPrefix)) {
            $frontendBasePath = $GLOBALS['TSFE']->absRefPrefix;
        // If not in CLI mode the base path can be determined from $_SERVER environment:
        } elseif (! Environment::isCli()) {
            $frontendBasePath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        }

        // Base path must be '/<pathSegements>/':
        if ($frontendBasePath !== '/') {
            $frontendBasePath = '/' . ltrim($frontendBasePath, '/');
            $frontendBasePath = rtrim($frontendBasePath, '/') . '/';
        }

        return $frontendBasePath;
    }
}
