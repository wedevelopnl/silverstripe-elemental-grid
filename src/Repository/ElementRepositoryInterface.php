<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use DNADesign\Elemental\Models\BaseElement;

interface ElementRepositoryInterface
{
    /** @param positive-int $id */
    public function findById(int $id): ?BaseElement;

    /**
     * Find all elements belonging to the given area IDs, ordered by Sort ASC, ID ASC.
     *
     * @param list<positive-int> $areaIds
     * @return list<BaseElement>
     */
    public function findByAreaIds(array $areaIds): array;
}
