<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\ElementalGrid\Contract\ReorderExecutorInterface;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;

class ReorderExecutor implements ReorderExecutorInterface
{
    public function __construct(
        private readonly ElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * @param non-negative-int $targetPosition
     * @return Result<BaseElement>
     */
    #[\Override]
    public function execute(BaseElement $element, ElementalArea $targetArea, int $targetPosition): Result
    {
        /** @var positive-int $targetAreaId */
        $targetAreaId = $targetArea->ID;

        /** @var positive-int $elementParentId */
        $elementParentId = $element->ParentID;
        $isCrossArea = $elementParentId !== $targetAreaId;

        $siblings = $this->elementRepository->findByAreaIds([$targetAreaId]);

        if (!$isCrossArea) {
            // Same-area: remove element from siblings list before re-inserting
            $siblings = array_values(array_filter(
                $siblings,
                static fn (BaseElement $sibling): bool => $sibling->ID !== $element->ID,
            ));
        }

        $targetPosition = min($targetPosition, count($siblings));
        array_splice($siblings, $targetPosition, 0, [$element]);

        if ($isCrossArea) {
            $element->ParentID = $targetAreaId;
        }

        try {
            $this->renumberAndWrite($siblings, $element, $isCrossArea);
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    /**
     * Renumber Sort values (1-based) and write only elements that changed.
     *
     * @param list<BaseElement> $siblings
     */
    private function renumberAndWrite(array $siblings, BaseElement $movedElement, bool $isCrossArea): void
    {
        foreach ($siblings as $index => $sibling) {
            $newSort = $index + 1;
            $sortChanged = $sibling->Sort !== $newSort;
            $isMovedCrossArea = $isCrossArea && $sibling->ID === $movedElement->ID;

            if (!$sortChanged && !$isMovedCrossArea) {
                continue;
            }

            $sibling->Sort = $newSort;
            $sibling->write();
        }
    }

    /**
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
