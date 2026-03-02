<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Models\BaseElement;
use WeDevelop\ElementalGrid\Model\Result;

interface HierarchyValidatorInterface
{
    /** @return Result<true> */
    public function validate(BaseElement $element): Result;
}
