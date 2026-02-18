<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Applied to BaseElement via YAML. Delegates hierarchy validation
 * to the centralized HierarchyValidationService.
 *
 * @extends Extension<\DNADesign\Elemental\Models\BaseElement>
 */
class HierarchyValidationExtension extends Extension
{
    public function updateValidate(ValidationResult $result): void
    {
        /** @var HierarchyValidatorInterface $service */
        $service = Injector::inst()->get(HierarchyValidatorInterface::class);
        $serviceResult = $service->validate($this->owner);

        /** @var array<array{message: string, messageType: string, messageCast: string, fieldName: string|null}> $messages */
        $messages = $serviceResult->getMessages();
        foreach ($messages as $message) {
            $result->addError($message['message']);
        }
    }
}
