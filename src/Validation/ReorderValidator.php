<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Contract\ReorderValidatorInterface;
use WeDevelop\Grid\Model\Result;
use WeDevelop\Grid\Model\ValidationError;

class ReorderValidator implements ReorderValidatorInterface
{
    /** @return Result<BaseElement> */
    #[\Override]
    public function validate(BaseElement $element, ElementalArea $targetArea): Result
    {
        if ($element->ParentID === $targetArea->ID) {
            return Result::ok($element);
        }

        return $this->checkHierarchyRules($element, $targetArea);
    }

    /** @return Result<BaseElement> */
    private function checkHierarchyRules(BaseElement $element, ElementalArea $targetArea): Result
    {
        $owner = $targetArea->getOwnerPage();
        if ($owner === null) {
            return Result::ok($element);
        }

        // Page-level: owner has ElementalPageExtension
        if ($owner->hasExtension(ElementalPageExtension::class)) {
            if ($element->config()->get('can_be_root') === false) {
                return Result::fail(new ValidationError(
                    message: sprintf(
                        '%s cannot be placed inside %s.',
                        $element->singular_name(),
                        $owner->singular_name(),
                    ),
                    field: 'placement',
                ));
            }

            return Result::ok($element);
        }

        if ($this->isElementAllowed($element::class, $owner)) {
            return Result::ok($element);
        }

        return Result::fail(new ValidationError(
            message: sprintf(
                '%s cannot be placed inside %s.',
                $element->singular_name(),
                $owner->singular_name(),
            ),
            field: 'placement',
        ));
    }

    /**
     * Check if element class is permitted by the owner's
     * allowed_elements / disallowed_elements config.
     *
     * @param class-string<BaseElement> $elementClass
     */
    private function isElementAllowed(string $elementClass, DataObject $owner): bool
    {
        $config = $owner->config();
        $stopInheritance = (bool) $config->get('stop_element_inheritance');

        $allowedElements = $stopInheritance
            ? $config->get('allowed_elements', Config::UNINHERITED)
            : $config->get('allowed_elements');

        if (is_array($allowedElements) && !in_array($elementClass, $allowedElements, true)) {
            return false;
        }

        $disallowedElements = $stopInheritance
            ? (array) $config->get('disallowed_elements', Config::UNINHERITED)
            : (array) $config->get('disallowed_elements');

        return !in_array($elementClass, $disallowedElements, true);
    }
}
