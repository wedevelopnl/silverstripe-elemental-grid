<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use WeDevelop\Grid\Model\GridElement;
use SilverStripe\Core\Injector\Injectable;

final class OrmGridElementRepository implements GridElementRepositoryInterface
{
    use Injectable;

    public function findById(int $id): ?GridElement
    {
        /** @var GridElement|null */
        return GridElement::get()->byID($id);
    }

    public function findByParentIds(array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        /** @var list<GridElement> */
        return GridElement::get()
            ->filter('ParentID', $parentIds)
            ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
            ->toArray();
    }
}
