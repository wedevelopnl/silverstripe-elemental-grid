<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;

interface ReorderExecutorInterface
{
    /**
     * Compute the new ordering. Mutates Sort (and ParentID for cross-area) on
     * affected elements in-place but does NOT persist.
     *
     * @param non-negative-int $targetPosition
     * @return list<BaseElement> Elements whose Sort or ParentID changed (need writing)
     */
    public function execute(BaseElement $element, ElementalArea $targetArea, int $targetPosition): array;
}
