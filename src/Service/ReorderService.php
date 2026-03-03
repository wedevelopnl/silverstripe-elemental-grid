<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Contract\ReorderExecutorInterface;
use WeDevelop\ElementalGrid\Contract\ReorderValidatorInterface;
use WeDevelop\ElementalGrid\Model\Result;

class ReorderService
{
    public function __construct(
        private readonly ReorderValidatorInterface $validator,
        private readonly ReorderExecutorInterface $executor,
        private readonly ElementPersistenceService $persistenceService,
    ) {
    }

    /**
     * @param positive-int|null $afterElementId
     * @return Result<BaseElement>
     */
    public function reorder(BaseElement $element, ElementalArea $targetArea, ?int $afterElementId): Result
    {
        $validationResult = $this->validator->validate($element, $targetArea);
        if ($validationResult->isErr()) {
            return $validationResult;
        }

        $executeResult = $this->executor->execute($element, $targetArea, $afterElementId);
        if ($executeResult->isErr()) {
            return Result::fail(...$executeResult->errors());
        }

        $dirtyElements = $executeResult->unwrap();

        $persistResult = $this->persistenceService->persistBatch($dirtyElements);
        if ($persistResult->isErr()) {
            return Result::fail(...$persistResult->errors());
        }

        return Result::ok($element);
    }
}
