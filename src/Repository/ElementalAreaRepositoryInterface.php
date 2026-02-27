<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Repository;

use DNADesign\Elemental\Models\ElementalArea;

interface ElementalAreaRepositoryInterface
{
    /** @param positive-int $id */
    public function findById(int $id): ?ElementalArea;
}
