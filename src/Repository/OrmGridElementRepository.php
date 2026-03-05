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

    public function findByParents(array $idsByClass): array
    {
        if ($idsByClass === []) {
            return [];
        }

        // Collect all parent IDs and classes for a combined filter
        $allParentIds = [];
        $allParentClasses = [];
        foreach ($idsByClass as $class => $ids) {
            $allParentClasses[] = $class;
            $allParentIds = array_merge($allParentIds, $ids);
        }

        /** @var list<GridElement> */
        return GridElement::get()
            ->filter([
                'ParentID' => array_unique($allParentIds),
                'ParentClass' => array_unique($allParentClasses),
            ])
            ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
            ->toArray();
    }
}
