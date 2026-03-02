<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Contract\ReorderExecutorInterface;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;

class ReorderExecutor implements ReorderExecutorInterface
{
    public function __construct(
        private readonly ElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * @param non-negative-int $targetPosition
     * @return list<BaseElement>
     */
    #[\Override]
    public function execute(BaseElement $element, ElementalArea $targetArea, int $targetPosition): array
    {
        /** @var positive-int $targetAreaId */
        $targetAreaId = $targetArea->ID;

        /** @var positive-int $elementParentId */
        $elementParentId = $element->ParentID;
        $isCrossArea = $elementParentId !== $targetAreaId;

        $siblings = $this->elementRepository->findByAreaIds([$targetAreaId]);

        if (!$isCrossArea) {
            // Same-area: remove element from siblings list before re-inserting
            $siblings = array_values(array_filter(
                $siblings,
                static fn (BaseElement $sibling): bool => $sibling->ID !== $element->ID,
            ));
        }

        $targetPosition = min($targetPosition, count($siblings));
        array_splice($siblings, $targetPosition, 0, [$element]);

        if ($isCrossArea) {
            $element->ParentID = $targetAreaId;
        }

        return $this->applySort($siblings, $element, $isCrossArea);
    }

    /**
     * Assign 1-based Sort values and return only elements that changed.
     *
     * @param list<BaseElement> $siblings
     * @return list<BaseElement>
     */
    private function applySort(array $siblings, BaseElement $movedElement, bool $isCrossArea): array
    {
        $dirty = [];

        foreach ($siblings as $index => $sibling) {
            $newSort = $index + 1;
            $sortChanged = $sibling->Sort !== $newSort;
            $isMovedCrossArea = $isCrossArea && $sibling->ID === $movedElement->ID;

            if ($sortChanged) {
                $sibling->Sort = $newSort;
            }

            if ($sortChanged || $isMovedCrossArea) {
                $dirty[] = $sibling;
            }
        }

        return $dirty;
    }
}
