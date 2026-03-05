<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use DNADesign\Elemental\Models\BaseElement;
use WeDevelop\Grid\Model\Result;

interface HierarchyValidatorInterface
{
    /** @return Result<BaseElement> */
    public function validate(BaseElement $element): Result;
}
