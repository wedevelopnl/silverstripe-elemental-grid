<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Repository;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Injector\Injectable;

final class OrmElementRepository implements ElementRepositoryInterface
{
    use Injectable;

    public function findById(int $id): ?BaseElement
    {
        /** @var BaseElement|null */
        return BaseElement::get()->byID($id);
    }

    public function findByAreaIds(array $areaIds): array
    {
        if ($areaIds === []) {
            return [];
        }

        /** @var list<BaseElement> */
        return BaseElement::get()
            ->filter('ParentID', $areaIds)
            ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
            ->toArray();
    }
}
