<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Contract\ReorderValidatorInterface;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Value\Result;
use WeDevelop\Grid\Value\ValidationError;

class ReorderValidator implements ReorderValidatorInterface
{
    use ElementAllowanceTrait;
    /** @return Result<GridElement> */
    #[\Override]
    public function validate(GridElement $element, DataObject $targetParent): Result
    {
        if ((int) $element->ParentID === (int) $targetParent->ID) {
            return Result::ok($element);
        }

        return $this->checkHierarchyRules($element, $targetParent);
    }

    /** @return Result<GridElement> */
    private function checkHierarchyRules(GridElement $element, DataObject $targetParent): Result
    {
        // Page-level: target parent is a SiteTree — check can_be_root
        if ($targetParent instanceof SiteTree) {
            if ($element->config()->get('can_be_root') === false) {
                return Result::fail(new ValidationError(
                    message: sprintf(
                        '%s cannot be placed at page level.',
                        $element->singular_name(),
                    ),
                    field: 'placement',
                ));
            }

            return Result::ok($element);
        }

        // Container-level: check allowed_elements / disallowed_elements on target
        if ($this->isElementAllowed($element::class, $targetParent)) {
            return Result::ok($element);
        }

        return Result::fail(new ValidationError(
            message: sprintf(
                '%s cannot be placed inside %s.',
                $element->singular_name(),
                $targetParent->singular_name(),
            ),
            field: 'placement',
        ));
    }

}
