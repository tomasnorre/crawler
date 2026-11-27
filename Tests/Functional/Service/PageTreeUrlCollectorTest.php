<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

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

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Tests\Functional\LanguageServiceTestTrait;
use AOE\Crawler\Value\QueueRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(PageTreeUrlCollector::class)]
final class PageTreeUrlCollectorTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    use LanguageServiceTestTrait;
    use SiteBasedTestTrait;

    /**
     * @noRector \Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
        ],
    ];

    protected array $coreExtensionsToLoad = ['info', 'seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private PageTreeUrlCollector $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setupBackendRequest();
        $this->setupBackendUser(2);
        $this->setupLanguageService();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_with_mount_points.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://acme.com/')]
        );

        // The collector is a private service, the command gets it with the DI configuration
        $this->subject = (new \ReflectionProperty(BuildQueueCommand::class, 'pageTreeUrlCollector'))->getValue(
            GeneralUtility::makeInstance(BuildQueueCommand::class)
        );
    }

    /**
     * Page 4 mounts page 2 without overlay, page 5 mounts page 2 with overlay ("mount_pid_ol").
     * Both list the subpages of page 2 before them, with URLs inside the mount point.
     */
    #[Test]
    public function collectAddsMountedPagesOfMountPoints(): void
    {
        $rows = $this->subject->collect(1, 1, new CrawlRun(0, 30));

        self::assertSame(
            [
                ['Root', 'https://acme.com/'],
                ['Mounted page', 'https://acme.com/mounted'],
                ['Subpage of mounted page', 'https://acme.com/mount-point/subpage'],
                // Without overlay, the mount point page is crawled as itself
                ['Mount point', 'https://acme.com/mount-point'],
                ['Subpage of mounted page', 'https://acme.com/mount-point-overlay/subpage'],
                // With overlay, the mounted page is crawled in place of the mount point page
                ['Mount point with overlay', 'https://acme.com/mount-point-overlay/'],
            ],
            array_map(
                // Strip the scheduled time "[1970-01-01 00:00] " from the URL
                static fn (QueueRow $row): array => [
                    $row->pageTitle,
                    (string) preg_replace('/^\\[[^]]+\\] /', '', $row->urls),
                ],
                $rows
            )
        );
    }
}
