<?php

declare(strict_types=1);

namespace AOE\Crawler\Widgets\Provider;

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
use TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface;

class QueueSizeDataProvider implements NumberWithIconDataProviderInterface
{
    /**
     * @var QueueRepository
     */
    private $queueRepository;

    public function __construct(QueueRepository $queueRepository)
    {
        $this->queueRepository = $queueRepository;
    }

    public function getNumber(): int
    {
        return $this->queueRepository->countUnprocessedItems();
    }
}
