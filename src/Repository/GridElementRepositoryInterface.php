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

    /**
     * Find all elements matching the given parent ID+class pairs, ordered by Sort ASC, ID ASC.
     *
     * Uses both ParentID and ParentClass to avoid false matches when IDs from
     * different tables (e.g. SiteTree and GridElement) collide.
     *
     * @param array<class-string, list<positive-int>> $idsByClass Map of parent class → parent IDs
     * @return list<GridElement>
     */
    public function findByParents(array $idsByClass): array;
}
