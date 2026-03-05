<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;

/**
 * Shared logic for checking whether an element class is permitted
 * by a parent's allowed_elements / disallowed_elements config.
 *
 * Used by both write-time validation (HierarchyValidationService) and
 * reorder-time validation (ReorderValidator) to avoid duplication.
 */
trait ElementAllowanceTrait
{
    /**
     * @param class-string $elementClass
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
