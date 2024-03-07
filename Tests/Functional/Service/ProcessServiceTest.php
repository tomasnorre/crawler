<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

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

use AOE\Crawler\Service\ProcessService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Service\ProcessService $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(ProcessService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCrawlerCliPathReturnsString(): void
    {
        // Check with phpPath set
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => '/usr/local/bin/foobar-binary-to-be-able-to-differ-the-test',
            'phpBinary' => '/usr/local/bin/php74',
        ];
        self::assertStringContainsString(
            '/usr/local/bin/foobar-binary-to-be-able-to-differ-the-test',
            $this->subject->getCrawlerCliPath()
        );

        // Check without phpPath set
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpBinary' => 'php',

        ];
        self::assertStringContainsString('php', $this->subject->getCrawlerCliPath());
    }
}
