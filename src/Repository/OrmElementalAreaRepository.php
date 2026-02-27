<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Repository;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Injector\Injectable;

final class OrmElementalAreaRepository implements ElementalAreaRepositoryInterface
{
    use Injectable;

    public function findById(int $id): ?ElementalArea
    {
        /** @var ElementalArea|null */
        return ElementalArea::get()->byID($id);
    }
}
