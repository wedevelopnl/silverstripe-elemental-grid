<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\Grid\Model\Result;

interface ReorderExecutorInterface
{
    /**
     * Compute the new ordering. Mutates Sort (and ParentID for cross-area) on
     * affected elements in-place but does NOT persist.
     *
     * @param positive-int|null $afterElementId ID of the element to insert after, or null for first position
     * @return Result<list<BaseElement>> Elements whose Sort or ParentID changed (need writing)
     */
    public function execute(BaseElement $element, ElementalArea $targetArea, ?int $afterElementId): Result;
}
