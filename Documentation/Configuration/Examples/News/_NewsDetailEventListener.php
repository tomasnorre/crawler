<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListeners;

use GeorgRinger\News\Event\NewsDetailActionEvent;

class NewsDetailEventListener
{
    public function __invoke(NewsDetailActionEvent $event): void
    {
        $assignedValues = $event->getAssignedValues();
        $newsItem = $assignedValues['newsItem'];
        $demand = $assignedValues['demand'];
        $settings = $assignedValues['settings'];

        if ($newsItem !== null) {
            $demandedCategories = $demand->getCategories();
            $itemCategories = $newsItem->getCategories()->toArray();
            $itemCategoryIds = \array_map(function ($category) {
                return (string) $category->getUid();
            }, $itemCategories);

            if (count($demandedCategories) > 0 && !$this::itemMatchesCategoryDemand(
                $settings['categoryConjunction'],
                $itemCategoryIds,
                $demandedCategories
            )) {
                $assignedValues['newsItem'] = null;
                $event->setAssignedValues($assignedValues);
            }
        }
    }

    protected static function itemMatchesCategoryDemand(
        string $categoryConjunction,
        array $itemCategoryIds,
        array $demandedCategories
    ): bool {
        $numOfDemandedCategories = \count($demandedCategories);
        $intersection = \array_intersect($itemCategoryIds, $demandedCategories);
        $numOfCommonItems = \count($intersection);

        switch ($categoryConjunction) {
            case 'AND':
                return $numOfCommonItems === $numOfDemandedCategories;
            case 'OR':
                return $numOfCommonItems > 0;
            case 'NOTAND':
                return $numOfCommonItems < $numOfDemandedCategories;
            case 'NOTOR':
                return $numOfCommonItems === 0;
        }
        return true;
    }
}
