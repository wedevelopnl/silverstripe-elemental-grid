<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Model\ElementNode;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;

/**
 * Builds a recursive element tree for a page using batch-loading to avoid N+1 queries.
 *
 * Uses a custom breadth-first loading strategy instead of SilverStripe's built-in
 * {@see \SilverStripe\ORM\DataList::eagerLoad()} because eager loading cannot handle
 * this tree structure:
 *
 * 1. Depth limit — eagerLoad() supports up to 3 levels of dot notation. Our hierarchy
 *    (Elements.ChildArea.Elements.ChildArea.Elements...) requires 7+ levels.
 * 2. Polymorphic ChildArea — The ChildArea has_one is defined on container subclasses
 *    (ElementSection, ElementRow, ElementColumn), not on BaseElement. The eager loader
 *    resolves relations from the queried class config and cannot discover ChildArea on
 *    a polymorphic BaseElement list.
 * 3. Recursive pattern — Dot notation is static; there is no way to express "repeat
 *    Elements → ChildArea N times" dynamically.
 *
 * The batch-loading approach issues one query per hierarchy depth level (O(depth) total),
 * collecting all elements at each level in a single query, then assembles the tree
 * in-memory from the pre-loaded data.
 */
class ElementTreeBuilder
{
    use Configurable;
    use Extensible;
    use Injectable;

    /** @var array<class-string, array<class-string, string>> */
    private array $allowedTypesCache = [];

    public function __construct(
        private readonly ElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * Build the full element tree for a page, keyed by elemental relation name.
     *
     * @return array<string, list<ElementNode>>
     */
    public function buildForPage(SiteTree $page): array
    {
        /** @var list<string>|false $relations */
        $relations = $page->getElementalRelations(); // @phpstan-ignore method.notFound (from ElementalAreasExtension)
        if ($relations === false || $relations === []) {
            return [];
        }

        /** @var array<string, list<ElementNode>> $tree */
        $tree = [];
        foreach ($relations as $relation) {
            $areaId = (int) $page->{$relation . 'ID'}; // @phpstan-ignore cast.int (ORM dynamic property)
            if ($areaId <= 0) {
                $tree[$relation] = [];
                continue;
            }

            $elementsByParent = $this->loadAllElements($areaId);
            $tree[$relation] = $this->assembleSubTree($elementsByParent, $areaId);
        }

        return $tree;
    }

    /**
     * Breadth-first batch loading: one query per hierarchy depth level.
     *
     * @param positive-int $rootAreaId
     * @return array<int, list<BaseElement>> Map of area ID → elements in that area
     */
    private function loadAllElements(int $rootAreaId): array
    {
        /** @var array<int, list<BaseElement>> $elementsByParent */
        $elementsByParent = [];
        $pendingAreaIds = [$rootAreaId];

        while ($pendingAreaIds !== []) {
            $elements = $this->elementRepository->findByAreaIds($pendingAreaIds);

            $nextAreaIds = [];

            foreach ($elements as $element) {
                $parentId = (int) $element->ParentID;
                $elementsByParent[$parentId] ??= [];
                $elementsByParent[$parentId][] = $element;

                if ($element instanceof ElementContainerInterface) {
                    $childAreaId = (int) $element->ChildAreaID; // @phpstan-ignore cast.int (ORM dynamic property)
                    if ($childAreaId > 0) {
                        $nextAreaIds[] = $childAreaId;
                    }
                }
            }

            $pendingAreaIds = $nextAreaIds;
        }

        return $elementsByParent;
    }

    /**
     * Recursively assemble tree nodes from pre-loaded element data.
     *
     * @param array<int, list<BaseElement>> $elementsByParent
     * @return list<ElementNode>
     */
    private function assembleSubTree(array $elementsByParent, int $areaId): array
    {
        $nodes = [];

        foreach ($elementsByParent[$areaId] ?? [] as $element) {
            if (!$element->canView()) {
                continue;
            }

            $nodes[] = $this->buildElementNode($element, $elementsByParent);
        }

        return $nodes;
    }

    /**
     * Build a single element node with base fields and optional container fields.
     *
     * @param array<int, list<BaseElement>> $elementsByParent
     */
    private function buildElementNode(BaseElement $element, array $elementsByParent): ElementNode
    {
        $containerType = null;
        $allowedTypes = null;
        $children = null;
        $gridSettings = null;

        if ($element instanceof ElementContainerInterface) {
            $containerType = $element->getContainerType();
            $allowedTypes = $this->getAllowedTypes($element);

            $childAreaId = (int) $element->ChildAreaID; // @phpstan-ignore cast.int (ORM dynamic property)
            $children = $childAreaId !== 0
                ? $this->assembleSubTree($elementsByParent, $childAreaId)
                : [];
        }

        if ($element instanceof ElementColumn) {
            $gridSettings = $element->getGridSettingsData();
        }

        $id = (int) $element->ID;
        $title = $element->Title;
        $obsoleteClassName = $element->getObsoleteClassName();
        $version = (int) $element->Version;
        $isPublished = $element->isPublished();
        $isLiveVersion = $element->isLiveVersion();
        $canDelete = $element->canDelete();
        $canPublish = $element->canPublish();
        $canUnpublish = (bool) $element->canUnpublish();
        $canCreate = $element->canCreate();


        /** @var array{typeName: string, actions: array{edit: string}, content: string} $blockSchema */
        $blockSchema = $element->getBlockSchema();

        /** @var array<string, mixed> $statusFlags */
        $statusFlags = $element->getStatusFlags();

        /** @var array<string, mixed> $extensions */
        $extensions = [];
        $this->extend('updateElementData', $element, $extensions);
        /** @var array<string, mixed> $extensions PHPStan: extend() widens by-ref params */

        return new ElementNode(
            id: $id,
            title: $title,
            blockSchema: $blockSchema,
            obsoleteClassName: $obsoleteClassName,
            version: $version,
            isPublished: $isPublished,
            isLiveVersion: $isLiveVersion,
            canDelete: $canDelete,
            canPublish: $canPublish,
            canUnpublish: $canUnpublish,
            canCreate: $canCreate,
            statusFlags: $statusFlags,
            containerType: $containerType,
            allowedTypes: $allowedTypes,
            children: $children,
            gridSettings: $gridSettings,
            extensions: $extensions,
        );
    }

    /**
     * Get allowed child element types for a container, cached by class name.
     *
     * @return array<class-string, string> FQCN → display label
     */
    private function getAllowedTypes(BaseElement $container): array
    {
        $className = $container::class;

        if (!isset($this->allowedTypesCache[$className])) {
            /** @var array<class-string, string> $types */
            $types = $container->getElementalTypes(); // @phpstan-ignore method.notFound (from ElementalAreasExtension)
            $this->allowedTypesCache[$className] = $types;
        }

        return $this->allowedTypesCache[$className];
    }
}
