<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\Result;
use WeDevelop\Grid\Model\ValidationError;

/**
 * Wraps SilverStripe write operations, translating framework
 * ValidationExceptions into Result failures.
 *
 * This is the single boundary where framework exceptions become domain Results.
 */
class ElementPersistenceService
{
    use Injectable;

    /**
     * Persist a new element, optionally inserting after another element.
     *
     * @param positive-int|null $afterElementId
     * @return Result<GridElement>
     */
    public function persistNew(GridElement $element, ?int $afterElementId = null): Result
    {
        try {
            $element->write();

            if ($afterElementId !== null) {
                $this->insertAfter($element, $afterElementId);
            }
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    /**
     * Persist a duplicated element after the original.
     *
     * @param positive-int $afterElementId
     * @return Result<GridElement>
     */
    public function persistDuplicate(GridElement $element, int $afterElementId): Result
    {
        try {
            $element->write();
            $this->insertAfter($element, $afterElementId);
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    /**
     * Insert an element after a reference sibling by adjusting Sort values.
     *
     * @param positive-int $afterElementId
     */
    private function insertAfter(GridElement $element, int $afterElementId): void
    {
        $afterElement = GridElement::get()->byID($afterElementId);
        if (!$afterElement instanceof GridElement) {
            return;
        }

        $newSort = (int) $afterElement->Sort + 1;

        // Bump sort values of elements after the reference
        /** @var GridElement $sibling */
        foreach (GridElement::get()
            ->filter([
                'ParentID' => $element->ParentID,
                'ParentClass' => $element->ParentClass,
            ])
            ->where(sprintf('"Sort" >= %d AND "GridElement"."ID" != %d', $newSort, (int) $element->ID))
            ->sort('Sort', 'ASC') as $sibling
        ) {
            $sibling->Sort = (int) $sibling->Sort + 1;
            $sibling->write();
        }

        $element->Sort = $newSort;
        $element->write();
    }

    /**
     * Write a batch of elements. Stops on first failure.
     *
     * @param list<GridElement> $elements
     * @return Result<null>
     */
    public function persistBatch(array $elements): Result
    {
        try {
            foreach ($elements as $element) {
                $element->write();
            }
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok(null);
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
