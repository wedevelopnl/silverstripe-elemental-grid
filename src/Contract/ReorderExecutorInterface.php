<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\Result;

interface ReorderExecutorInterface
{
    /**
     * Compute the new ordering. Mutates Sort (and ParentID for cross-parent) on
     * affected elements in-place but does NOT persist.
     *
     * @param positive-int|null $afterElementId ID of the element to insert after, or null for first position
     * @return Result<list<GridElement>> Elements whose Sort or ParentID changed (need writing)
     */
    public function execute(GridElement $element, DataObject $targetParent, ?int $afterElementId): Result;
}
