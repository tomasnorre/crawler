<?php
declare(strict_types=1);

namespace AOE\Crawler\Value;

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

use Assert\Assert;

final class ModuleMenu
{
    /** @var CrawlAction[] */
    private $crawlActions;

    public function __construct(array $crawlActions)
    {
        Assert::thatAll($crawlActions)
            ->isInstanceOf(CrawlAction::class);

        $this->crawlActions = $crawlActions;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            array_map(function (string $item): CrawlAction {
                return new CrawlAction($item, 'label');
            }, array_keys($array['crawlaction']))
        );
    }

    /**
     * @return CrawlAction[]
     */
    public function getCrawlActions(): array
    {
        return $this->crawlActions;
    }
}
