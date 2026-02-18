<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\DataObject;

class HierarchyValidationService implements HierarchyValidatorInterface
{
    #[\Override]
    public function validate(BaseElement $element): ValidationResult
    {
        $result = ValidationResult::create();

        $parent = $element->Parent();
        if (!$parent->exists()) {
            return $result;
        }

        $owner = $parent->getOwnerPage();
        if ($owner === null) {
            return $result;
        }

        // Page-level: owner has ElementalPageExtension (applied to any SiteTree subclass)
        if ($owner->hasExtension(ElementalPageExtension::class)) {
            if ($element->config()->get('can_be_root') === false) {
                $result->addError(sprintf(
                    '%s cannot be placed inside %s.',
                    $element->singular_name(),
                    $owner->singular_name(),
                ));
            }

            return $result;
        }

        if ($this->isElementAllowed($element::class, $owner)) {
            return $result;
        }

        $result->addError(sprintf(
            '%s cannot be placed inside %s.',
            $element->singular_name(),
            $owner->singular_name(),
        ));

        return $result;
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
