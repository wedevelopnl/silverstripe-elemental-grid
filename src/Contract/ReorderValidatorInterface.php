<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\Result;

interface ReorderValidatorInterface
{
    /** @return Result<GridElement> */
    public function validate(GridElement $element, DataObject $targetParent): Result;
}
