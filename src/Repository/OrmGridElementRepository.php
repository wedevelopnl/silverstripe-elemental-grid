<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\Section;

final class OrmGridElementRepository implements GridElementRepositoryInterface
{
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

    public function findByParents(array $idsByClass, ?string $zone = null): array
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

        $filter = [
            'ParentID' => array_unique($allParentIds),
            'ParentClass' => array_unique($allParentClasses),
        ];

        // Zone only exists on the Section table — query Section directly when zone-filtering
        if ($zone !== null) {
            $filter['Zone'] = $zone;

            /** @var list<GridElement> */
            return Section::get()
                ->filter($filter)
                ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
                ->toArray();
        }

        /** @var list<GridElement> */
        return GridElement::get()
            ->filter($filter)
            ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
            ->toArray();
    }
}
