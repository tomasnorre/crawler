<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;

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

use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Evaluates HTTP headers and checks if Crawler should register itself.
 */
class FrontendUserAuthenticator implements MiddlewareInterface
{
    protected string $headerName = 'X-T3CRAWLER';
    protected Context $context;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        ?Context $context = null
    ) {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    /**
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var JsonCompatibilityConverter $jsonCompatibilityConverter */
        $jsonCompatibilityConverter = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);

        $crawlerInformation = $request->getHeaderLine($this->headerName);
        if (empty($crawlerInformation)) {
            return $handler->handle($request);
        }

        // Authenticate crawler request:
        //@todo: ask service to exclude current call for special reasons: for example no relevance because the language version is not affected
        [$queueId, $hash] = explode(':', $crawlerInformation);
        $queueRec = $this->findByQueueId($queueId);

        // If a crawler record was found and hash was matching, set it up
        if (! $this->isRequestHashMatchingQueueRecord($queueRec, $hash)) {
            return GeneralUtility::makeInstance(ErrorController::class)->unavailableAction(
                $request,
                'No crawler entry found'
            );
        }

        $queueParameters = $jsonCompatibilityConverter->convert($queueRec['parameters']);
        $request = $request->withAttribute('tx_crawler', $queueParameters);

        // Now ensure to set the proper user groups
        if (is_array($queueParameters)) {
            $grList = $queueParameters['feUserGroupList'] ?? '';
            if ($grList) {
                $frontendUser = $this->getFrontendUser($grList, $request);

                // we have to set the fe user group to the user aspect since indexed_search only reads the user aspect
                // to get the groups. otherwise groups are ignored during indexing.
                // we need to add the groups 0, and -2 too, like the getGroupIds getter does.
                $this->context->setAspect(
                    'frontend.user',
                    GeneralUtility::makeInstance(UserAspect::class, $frontendUser, explode(',', '0,-2,' . $grList))
                );
            }
        }

        return $handler->handle($request);
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    private function isRequestHashMatchingQueueRecord(?array $queueRec, string $hash): bool
    {
        return is_array($queueRec) && hash_equals(
            $hash,
            md5(
                $queueRec['qid'] . '|' . $queueRec['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
            )
        );
    }

    /**
     * @return mixed|string|\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    private function getFrontendUser(string $grList, ServerRequestInterface $request)
    {
        /** @var FrontendUserAuthentication $frontendUser */
        $frontendUser = $request->getAttribute('frontend.user');
        $frontendUser->user[$frontendUser->usergroup_column] = '0,-2,' . $grList;
        $frontendUser->fetchGroupData($request);
        $frontendUser->user['uid'] = PHP_INT_MAX;
        return $frontendUser;
    }

    private function findByQueueId(string $queueId): array
    {
        $queueRec = $this->queryBuilder
            ->select('*')
            ->from(QueueRepository::TABLE_NAME)->where(
                $this->queryBuilder->expr()->eq('qid', $this->queryBuilder->createNamedParameter($queueId))
            )->executeQuery()
            ->fetch();
        return is_array($queueRec) ? $queueRec : [];
    }
}
