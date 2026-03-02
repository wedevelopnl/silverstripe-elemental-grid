<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Contract\ReorderValidatorInterface;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

class ReorderValidator implements ReorderValidatorInterface
{
    /** @return Result<BaseElement> */
    #[\Override]
    public function validate(BaseElement $element, ElementalArea $targetArea): Result
    {
        if ($element->ParentID === $targetArea->ID) {
            return Result::ok($element);
        }

        // Circular reference check: only containers can create cycles
        if ($element instanceof ElementContainerInterface) {
            $circularResult = $this->checkCircularReference($element, $targetArea);
            if ($circularResult !== null) {
                return $circularResult;
            }
        }

        return $this->checkHierarchyRules($element, $targetArea);
    }

    /**
     * Walk from targetArea upward through the ownership chain.
     * If the element being moved is found as an ancestor, it would create a cycle.
     *
     * @return Result<BaseElement>|null Null means no circular reference found
     */
    private function checkCircularReference(BaseElement $element, ElementalArea $area): ?Result
    {
        $current = $area->getOwnerPage();

        while ($current !== null) {
            if ($current instanceof BaseElement && $current->ID === $element->ID) {
                return Result::fail(new ValidationError(
                    message: sprintf(
                        'Moving %s here would create a circular reference.',
                        $element->singular_name(),
                    ),
                    field: 'placement',
                ));
            }

            if (!$current instanceof BaseElement) {
                break;
            }

            $parentArea = $current->Parent();
            if (!$parentArea->exists()) {
                break;
            }

            $current = $parentArea->getOwnerPage();
        }

        return null;
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
