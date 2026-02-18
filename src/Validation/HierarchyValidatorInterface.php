<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Validation\ValidationResult;

interface HierarchyValidatorInterface
{
    public function validate(BaseElement $element): ValidationResult;
}
