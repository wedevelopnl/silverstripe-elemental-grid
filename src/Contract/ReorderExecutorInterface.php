<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Model\Result;

interface ReorderExecutorInterface
{
    /**
     * @param non-negative-int $targetPosition
     * @return Result<BaseElement>
     */
    public function execute(BaseElement $element, ElementalArea $targetArea, int $targetPosition): Result;
}
