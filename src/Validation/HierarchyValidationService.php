<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Value\Result;
use WeDevelop\Grid\Value\ValidationError;

class HierarchyValidationService implements HierarchyValidatorInterface
{
    /** @return Result<GridElement> */
    #[\Override]
    public function validate(GridElement $element): Result
    {
        $parent = $element->Parent();
        if ($parent === null || !$parent->exists()) { // @phpstan-ignore identical.alwaysFalse (polymorphic has_one returns null when ParentClass is empty)
            return Result::ok($element);
        }

        // Page-level: parent is a SiteTree — check can_be_root
        if ($parent instanceof SiteTree) {
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

        // Container-level: check allowed_elements / disallowed_elements on parent
        if ($this->isElementAllowed($element::class, $parent)) {
            return Result::ok($element);
        }

        return Result::fail(new ValidationError(
            message: sprintf(
                '%s cannot be placed inside %s.',
                $element->singular_name(),
                $parent->singular_name(),
            ),
            field: 'placement',
        ));
    }

    /**
     * Check if element class is permitted by the parent's
     * allowed_elements / disallowed_elements config.
     *
     * @param class-string<GridElement> $elementClass
     */
    private function isElementAllowed(string $elementClass, DataObject $parent): bool
    {
        $config = $parent->config();
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
