<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Value\Result;

interface HierarchyValidatorInterface
{
    /** @return Result<GridElement> */
    public function validate(GridElement $element): Result;
}
