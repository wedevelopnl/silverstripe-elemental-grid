<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use WeDevelop\Grid\Model\GridElement;

interface GridElementRepositoryInterface
{
    /** @param positive-int $id */
    public function findById(int $id): ?GridElement;

    /**
     * Find all elements belonging to the given parent IDs, ordered by Sort ASC, ID ASC.
     *
     * @param list<positive-int> $parentIds
     * @return list<GridElement>
     */
    public function findByParentIds(array $parentIds): array;
}
