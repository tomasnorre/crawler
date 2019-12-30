<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;

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

use AOE\Crawler\Domain\Repository\QueueRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Evaluates HTTP headers and checks if Crawler should register itself.
 * Needs to be run after TSFE initialization AND Frontend User Authentication.
 *
 * Once done, the queue is fetched, and then the frontend request runs through.
 *
 * Finally, at the very end, if the crawler is still running, output the data and replace the response.
 */
class CrawlerInitialization implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $headerName = 'X-T3CRAWLER';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @param Context|null $context
     */
    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->queueRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(QueueRepository::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $crawlerInformation = $request->getHeaderLine($this->headerName) ?? null;
        if (empty($crawlerInformation)) {
            return $handler->handle($request);
        }

        // Authenticate crawler request:
        //@todo: ask service to exclude current call for special reasons: for example no relevance because the language version is not affected
        [$queueId, $hash] = explode(':', $crawlerInformation);
        $queueRec = $this->queueRepository->findByQueueId($queueId);

        // If a crawler record was found and hash was matching, set it up
        if (!$this->isRequestHashMatchingQueueRecord($queueRec, $hash)) {
            return GeneralUtility::makeInstance(ErrorController::class)->unavailableAction($request, 'No crawler entry found');
        }

        $queueParameters = unserialize($queueRec['parameters']);
        $GLOBALS['TSFE']->applicationData['tx_crawler']['running'] = true;
        $GLOBALS['TSFE']->applicationData['tx_crawler']['parameters'] = $queueParameters;
        $GLOBALS['TSFE']->applicationData['tx_crawler']['log'] = [];
        $request = $request->withAttribute('tx_crawler', $queueParameters);

        // Now ensure to set the proper user groups
        $grList = $queueParameters['feUserGroupList'];
        if ($grList) {
            if (!is_array($GLOBALS['TSFE']->fe_user->user)) {
                $GLOBALS['TSFE']->fe_user->user = [];
            }
            $GLOBALS['TSFE']->fe_user->user['usergroup'] = $grList;
            $GLOBALS['TSFE']->applicationData['tx_crawler']['log'][] = 'User Groups: ' . $grList;
            // @todo: set the frontend user aspect again.
        }

        // Execute the frontend request as is
        $handler->handle($request);

        $GLOBALS['TSFE']->applicationData['tx_crawler']['vars'] = [
            'id' => $GLOBALS['TSFE']->id,
            'gr_list' => implode(',', $this->context->getAspect('frontend.user')->getGroupIds()),
            'no_cache' => $GLOBALS['TSFE']->no_cache,
        ];

        $this->runPollSuccessHooks();

        // Output log data for crawler (serialized content):
        $content = serialize($GLOBALS['TSFE']->applicationData['tx_crawler']);
        $response = new Response();
        $response->getBody()->write($content);
        return $response;
    }

    protected function isRequestHashMatchingQueueRecord(?array $queueRec, string $hash): bool
    {
        return is_array($queueRec) && $hash === md5($queueRec['qid'] . '|' . $queueRec['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }

    /**
     * Required because some extensions (staticpub) might never be requested to run due to some Core side effects
     * and since this is considered as error the crawler should handle it properly
     */
    protected function runPollSuccessHooks(): void
    {
        if (!is_array($GLOBALS['TSFE']->applicationData['tx_crawler']['content']['parameters']['procInstructions'])) {
            return;
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] ?? [] as $pollable) {
            if (in_array($pollable, $GLOBALS['TSFE']->applicationData['tx_crawler']['content']['parameters']['procInstructions'])) {
                if (empty($GLOBALS['TSFE']->applicationData['tx_crawler']['success'][$pollable])) {
                    $GLOBALS['TSFE']->applicationData['tx_crawler']['errorlog'][] = 'Error: Pollable extension (' . $pollable . ') did not complete successfully.';
                }
            }
        }
    }
}
