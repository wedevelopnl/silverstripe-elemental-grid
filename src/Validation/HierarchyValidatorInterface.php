<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Models\BaseElement;
use WeDevelop\ElementalGrid\Model\Result;

interface HierarchyValidatorInterface
{
    /** @return Result<BaseElement> */
    public function validate(BaseElement $element): Result;
}
