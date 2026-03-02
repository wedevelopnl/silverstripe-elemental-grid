<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Model\Result;

interface ReorderValidatorInterface
{
    /** @return Result<BaseElement> */
    public function validate(BaseElement $element, ElementalArea $targetArea): Result;
}
