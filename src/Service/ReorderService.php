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
     * @param non-negative-int $targetPosition
     * @return Result<BaseElement>
     */
    public function reorder(BaseElement $element, ElementalArea $targetArea, int $targetPosition): Result
    {
        $validationResult = $this->validator->validate($element, $targetArea);
        if ($validationResult->isErr()) {
            return $validationResult;
        }

        $dirtyElements = $this->executor->execute($element, $targetArea, $targetPosition);

        $persistResult = $this->persistenceService->persistBatch($dirtyElements);
        if ($persistResult->isErr()) {
            return $persistResult;
        }

        return Result::ok($element);
    }
}
