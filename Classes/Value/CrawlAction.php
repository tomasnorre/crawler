<?php

declare(strict_types=1);

namespace AOE\Crawler\Value;

use Assert\Assert;

final class CrawlAction
{
    /**
     * @var string
     */
    private $crawlAction;

    /**
     * @var string
     */
    private $crawlActionLabel;

    public function __construct(string $crawlAction, string $crawlActionLabel)
    {
        Assert::that($crawlAction)
            ->inArray(['start', 'log', 'multiprocess']);

        $this->crawlAction = $crawlAction;
        $this->crawlActionLabel = $crawlActionLabel;
    }

    public function __toString(): string
    {
        return $this->crawlAction;
    }

    public function getCrawlActionLabel(): string
    {
        return $this->crawlActionLabel;
    }

}
