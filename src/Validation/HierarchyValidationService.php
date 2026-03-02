<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

class HierarchyValidationService implements HierarchyValidatorInterface
{
    /** @return Result<true> */
    #[\Override]
    public function validate(BaseElement $element): Result
    {
        $parent = $element->Parent();
        if (!$parent->exists()) {
            /** @var Result<true> */
            return Result::ok(true);
        }

        $owner = $parent->getOwnerPage();
        if ($owner === null) {
            /** @var Result<true> */
            return Result::ok(true);
        }

        // Page-level: owner has ElementalPageExtension (applied to any SiteTree subclass)
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

            /** @var Result<true> */
            return Result::ok(true);
        }

        if ($this->isElementAllowed($element::class, $owner)) {
            /** @var Result<true> */
            return Result::ok(true);
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
     * Mirrors ElementalAreasExtension::getElementalTypes() logic.
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
