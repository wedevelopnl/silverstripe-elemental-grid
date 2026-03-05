<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Service;

use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Contract\ReorderExecutorInterface;
use WeDevelop\Grid\Contract\ReorderValidatorInterface;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Value\Result;

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
     * @return Result<GridElement>
     */
    public function reorder(GridElement $element, DataObject $targetParent, ?int $afterElementId): Result
    {
        $validationResult = $this->validator->validate($element, $targetParent);
        if ($validationResult->isErr()) {
            return $validationResult;
        }

        $executeResult = $this->executor->execute($element, $targetParent, $afterElementId);
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
