<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Contract\ReorderExecutorInterface;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;

class ReorderExecutor implements ReorderExecutorInterface
{
    public function __construct(
        private readonly ElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * @param positive-int|null $afterElementId
     * @return Result<list<BaseElement>>
     */
    #[\Override]
    public function execute(BaseElement $element, ElementalArea $targetArea, ?int $afterElementId): Result
    {
        /** @var positive-int $targetAreaId */
        $targetAreaId = $targetArea->ID;

        /** @var positive-int $sourceAreaId */
        $sourceAreaId = $element->ParentID;
        $isCrossArea = $sourceAreaId !== $targetAreaId;

        // Fetch target siblings, always excluding the moved element
        $targetSiblings = $this->excludeElement(
            $this->elementRepository->findByAreaIds([$targetAreaId]),
            $element,
        );

        $insertionIndex = $this->resolveInsertionIndex($targetSiblings, $afterElementId);
        if ($insertionIndex === null) {
            return Result::fail(new ValidationError(
                message: 'The reference element no longer exists in the target area.',
                field: 'afterElementID',
            ));
        }

        array_splice($targetSiblings, $insertionIndex, 0, [$element]);

        if (!$isCrossArea) {
            return Result::ok($this->reindex($targetSiblings));
        }

        // Cross-area: reassign ParentID, reindex target, then reindex source to close gaps
        $element->ParentID = $targetAreaId;

        $dirty = $this->reindex($targetSiblings, $element);

        $sourceSiblings = $this->excludeElement(
            $this->elementRepository->findByAreaIds([$sourceAreaId]),
            $element,
        );

        return Result::ok([...$dirty, ...$this->reindex($sourceSiblings)]);
    }

    /**
     * Resolve where to insert the element in the siblings list.
     *
     * @param list<BaseElement> $siblings
     * @param positive-int|null $afterElementId
     * @return int<0, max>|null Index to splice at, or null if afterElementId not found
     */
    private function resolveInsertionIndex(array $siblings, ?int $afterElementId): ?int
    {
        if ($afterElementId === null) {
            return 0;
        }

        foreach ($siblings as $index => $sibling) {
            if ($sibling->ID === $afterElementId) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * Remove a specific element from a siblings list.
     *
     * @param list<BaseElement> $siblings
     * @return list<BaseElement>
     */
    private function excludeElement(array $siblings, BaseElement $element): array
    {
        return array_values(array_filter(
            $siblings,
            static fn (BaseElement $sibling): bool => $sibling->ID !== $element->ID,
        ));
    }

    /**
     * Assign 1-based Sort values and return only elements that changed.
     *
     * For cross-area moves, the moved element's ParentID has changed even if its
     * Sort stays the same — pass it as $alwaysDirty to force inclusion.
     *
     * @param list<BaseElement> $siblings
     * @return list<BaseElement>
     */
    private function reindex(array $siblings, ?BaseElement $alwaysDirty = null): array
    {
        $dirty = [];

        foreach ($siblings as $index => $sibling) {
            $newSort = $index + 1;

            if ($sibling->Sort !== $newSort) {
                $sibling->Sort = $newSort;
                $dirty[] = $sibling;
            } elseif ($alwaysDirty !== null && $sibling->ID === $alwaysDirty->ID) {
                $dirty[] = $sibling;
            }
        }

        return $dirty;
    }
}
