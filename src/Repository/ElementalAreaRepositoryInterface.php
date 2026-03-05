<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use DNADesign\Elemental\Models\ElementalArea;

interface ElementalAreaRepositoryInterface
{
    /** @param positive-int $id */
    public function findById(int $id): ?ElementalArea;
}
