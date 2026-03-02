<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Services\ReorderElements;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

/**
 * Wraps SilverStripe write/reorder operations, translating
 * framework ValidationExceptions into Result failures.
 *
 * This is the single boundary where framework exceptions become domain Results.
 */
class ElementPersistenceService
{
    use Injectable;

    /**
     * Persist a new element, optionally inserting after another element.
     *
     * @return Result<BaseElement>
     */
    public function persistNew(BaseElement $element, ?int $afterElementId = null): Result
    {
        try {
            if ($afterElementId !== null) {
                $this->reorderElement($element, $afterElementId);
            } else {
                $element->write();
            }
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    /**
     * Persist a duplicated element after the original.
     *
     * @return Result<BaseElement>
     */
    public function persistDuplicate(BaseElement $element, int $afterElementId): Result
    {
        try {
            $this->reorderElement($element, $afterElementId);
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    private function reorderElement(BaseElement $element, int $afterElementId): void
    {
        /** @var ReorderElements $reorderer */
        $reorderer = Injector::inst()->create(ReorderElements::class, $element);
        $reorderer->reorder($afterElementId);
    }

    /**
     * Translate a SilverStripe ValidationException into ValidationError list.
     *
     * @return non-empty-list<ValidationError>
     */
    private function translateValidationException(ValidationException $e): array
    {
        /** @var array<array{message: string, fieldName: string}> $messages */
        $messages = $e->getResult()->getMessages();

        if ($messages === []) {
            return [new ValidationError(message: 'Validation failed.')];
        }

        $errors = [];

        foreach ($messages as $msg) {
            $errors[] = new ValidationError(
                message: $msg['message'],
                field: $msg['fieldName'] !== '' ? $msg['fieldName'] : null,
            );
        }

        /** @var non-empty-list<ValidationError> $errors Guaranteed non-empty: $messages is non-empty */
        return $errors;
    }
}
