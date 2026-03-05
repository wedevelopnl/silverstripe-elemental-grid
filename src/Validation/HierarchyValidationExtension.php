<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Validation;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Applied to GridElement via YAML. Delegates hierarchy validation
 * to the centralized HierarchyValidationService, then translates
 * Result errors into the framework's ValidationResult.
 *
 * @extends Extension<\WeDevelop\Grid\Model\GridElement>
 */
class HierarchyValidationExtension extends Extension
{
    public function updateValidate(ValidationResult $result): void
    {
        /** @var HierarchyValidatorInterface $service */
        $service = Injector::inst()->get(HierarchyValidatorInterface::class);
        $serviceResult = $service->validate($this->owner);

        if ($serviceResult->isOk()) {
            return;
        }

        foreach ($serviceResult->errors() as $error) {
            $result->addError($error->message);
        }
    }
}
