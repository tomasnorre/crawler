<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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
use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class UrlService
{
    /**
     * Build a URL from a Page and the Query String. If the page has a Site configuration, it can be built by using
     * the Site instance.
     *
     * @param int $httpsOrHttp see tx_crawler_configuration.force_ssl
     * @throws SiteNotFoundException
     * @throws InvalidRouteArgumentsException
     * @throws Exception
     */
    public function getUrlFromPageAndQueryParameters(
        int $pageId,
        string $queryString,
        ?string $alternativeBaseUrl,
        int $httpsOrHttp
    ): ?UriInterface {
        $url = new Uri();
        $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId($pageId);
        if ($site instanceof Site) {
            $url = $this->getUrlFromSite($site, $pageId, $queryString, $alternativeBaseUrl);
            if (!$url instanceof UriInterface) {
                return null;
            }
        }

        return $this->applyScheme($url, $httpsOrHttp);
    }

    /**
     * @throws InvalidRouteArgumentsException
     * @throws Exception
     */
    private function getUrlFromSite(
        Site $site,
        int $pageId,
        string $queryString,
        ?string $alternativeBaseUrl
    ): ?UriInterface {
        $queryString = ltrim($queryString, '?&');
        $queryParts = [];
        parse_str($queryString, $queryParts);
        unset($queryParts['id']);

        if (!empty($queryParts['L']) && !$this->pageExistsInLanguage($site, $pageId, (int) $queryParts['L'])) {
            return null;
        }

        // workaround as long as we don't have native language support in crawler configurations
        if (isset($queryParts['L'])) {
            $queryParts['_language'] = $queryParts['L'];
            unset($queryParts['L']);
            // Throws an exception if the site has no such language
            $site->getLanguageById((int) $queryParts['_language']);
        }
        $url = $site->getRouter()->generateUri($pageId, $queryParts);
        if (!empty($alternativeBaseUrl)) {
            $url = $this->applyAlternativeBaseUrl($url, new Uri($alternativeBaseUrl));
        }
        return $url;
    }

    /**
     * Checks whether the page, or one of its translations into the language or its fallback languages, exists
     *
     * @throws Exception
     */
    private function pageExistsInLanguage(Site $site, int $pageId, int $languageId): bool
    {
        $requestedLanguage = $site->getLanguageById($languageId);
        $languages = array_merge([$languageId], $requestedLanguage->getFallbackLanguageIds());

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $query = $queryBuilder->select('*')
            ->from('pages')
            ->orWhere(
                $queryBuilder->expr()->eq('uid', $pageId),
                $queryBuilder->expr()->eq('l10n_parent', $pageId)
            )->andWhere($queryBuilder->expr()->in('sys_language_uid', $languages))->executeQuery();
        $rows = $query->fetchAssociative();

        return !empty($rows);
    }

    private function applyAlternativeBaseUrl(UriInterface $url, UriInterface $alternativeBaseUrl): UriInterface
    {
        $url = $url->withHost($alternativeBaseUrl->getHost());
        $url = $url->withScheme($alternativeBaseUrl->getScheme());
        $url = $url->withPort($alternativeBaseUrl->getPort());
        if ($userInfo = $alternativeBaseUrl->getUserInfo()) {
            $url = $url->withUserInfo($userInfo);
        }
        return $url;
    }

    /**
     * @param int $httpsOrHttp see tx_crawler_configuration.force_ssl
     */
    private function applyScheme(UriInterface $url, int $httpsOrHttp): UriInterface
    {
        if ($httpsOrHttp === -1) {
            return $url->withScheme('http');
        }
        if ($httpsOrHttp === 1) {
            return $url->withScheme('https');
        }
        return $url;
    }

    /**
     * Compiling URLs from parameter array (output of expandParameters())
     * The number of URLs will be the multiplication of the number of parameter values for each key
     *
     * @param array<array-key, array<array-key, mixed>> $paramArray Output of expandParameters(): Array with keys (GET var names) and for each an array of values
     * @param list<string> $urls URLs accumulated in this array (for recursion)
     * @return list<string>
     */
    public function compileUrls(array $paramArray, array $urls, int $maxUrlToCompile = 10): array
    {
        if ($paramArray === []) {
            return $urls;
        }
        $varName = key($paramArray);
        $valueSet = array_shift($paramArray);

        // Traverse value set:
        $newUrls = [];
        foreach ($urls as $url) {
            foreach ($valueSet as $val) {
                if (count($newUrls) < $maxUrlToCompile) {
                    $newUrls[] = $url . $this->buildQueryParameter((string) $varName, (string) $val);
                }
            }
        }
        return $this->compileUrls($paramArray, $newUrls, $maxUrlToCompile);
    }

    /**
     * Returns the query parameter to append to a URL, or an empty string for an empty value
     */
    private function buildQueryParameter(string $name, string $value): string
    {
        if ($value === '') {
            return '';
        }
        return '&' . rawurlencode($name) . '=' . rawurlencode($value);
    }
}
