<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Service;

use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Contract\ReorderExecutorInterface;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\Section;
use WeDevelop\Grid\Value\Result;
use WeDevelop\Grid\Value\ValidationError;
use WeDevelop\Grid\Repository\GridElementRepositoryInterface;

class ReorderExecutor implements ReorderExecutorInterface
{
    public function __construct(
        private readonly GridElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * @param positive-int|null $afterElementId
     * @return Result<list<GridElement>>
     */
    #[\Override]
    public function execute(GridElement $element, DataObject $targetParent, ?int $afterElementId): Result
    {
        /** @var positive-int $targetParentId */
        $targetParentId = $targetParent->ID;

        /** @var positive-int $sourceParentId */
        $sourceParentId = $element->ParentID;
        $isCrossParent = $sourceParentId !== $targetParentId;

        // Fetch target siblings, excluding the moved element.
        // For Sections, scope to the element's zone. Sort values are per-zone-per-parent:
        // main zone has Sort 1,2,3 and sidebar zone independently has Sort 1,2,3.
        // This is safe because all queries (readTree, ensureSortSet) filter by zone.
        $allTargetSiblings = $this->elementRepository->findByParentIds([$targetParentId]);
        $targetSiblings = $element instanceof Section
            ? $this->filterByZone($allTargetSiblings, $element->Zone ?: '')
            : $allTargetSiblings;
        $targetSiblings = $this->excludeElement($targetSiblings, $element);

        $insertionIndex = $this->resolveInsertionIndex($targetSiblings, $afterElementId);
        if ($insertionIndex === null) {
            return Result::fail(new ValidationError(
                message: 'The reference element no longer exists in the target parent.',
                field: 'afterElementID',
            ));
        }

        array_splice($targetSiblings, $insertionIndex, 0, [$element]);

        if (!$isCrossParent) {
            return Result::ok($this->reindex($targetSiblings));
        }

        // Cross-parent: reassign ParentID and ParentClass, reindex target, then reindex source to close gaps
        $element->ParentID = $targetParentId;
        $element->ParentClass = $targetParent::class;

        $dirty = $this->reindex($targetSiblings, $element);

        $allSourceSiblings = $this->elementRepository->findByParentIds([$sourceParentId]);
        $sourceSiblings = $element instanceof Section
            ? $this->filterByZone($allSourceSiblings, $element->Zone ?: '')
            : $allSourceSiblings;
        $sourceSiblings = $this->excludeElement($sourceSiblings, $element);

        return Result::ok([...$dirty, ...$this->reindex($sourceSiblings)]);
    }

    /**
     * Resolve where to insert the element in the siblings list.
     *
     * @param list<GridElement> $siblings
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
     * Filter siblings to only Sections matching the given zone.
     *
     * @param list<GridElement> $siblings
     * @return list<GridElement>
     */
    private function filterByZone(array $siblings, string $zone): array
    {
        return array_values(array_filter(
            $siblings,
            static fn (GridElement $s): bool => $s instanceof Section && ($s->Zone ?: '') === $zone,
        ));
    }

    /**
     * Remove a specific element from a siblings list.
     *
     * @param list<GridElement> $siblings
     * @return list<GridElement>
     */
    private function excludeElement(array $siblings, GridElement $element): array
    {
        return array_values(array_filter(
            $siblings,
            static fn (GridElement $sibling): bool => $sibling->ID !== $element->ID,
        ));
    }

    /**
     * Assign 1-based Sort values and return only elements that changed.
     *
     * For cross-parent moves, the moved element's ParentID has changed even if its
     * Sort stays the same — pass it as $alwaysDirty to force inclusion.
     *
     * @param list<GridElement> $siblings
     * @return list<GridElement>
     */
    private function reindex(array $siblings, ?GridElement $alwaysDirty = null): array
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
