<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Service;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use WeDevelop\Grid\Contract\ContainerInterface;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Model\GridNode;
use WeDevelop\Grid\Repository\GridElementRepositoryInterface;

/**
 * Builds a recursive element tree for a page using batch-loading to avoid N+1 queries.
 *
 * Uses a breadth-first loading strategy: one query per hierarchy depth level.
 * Collects all elements at each level in a single query, then assembles the tree
 * in-memory from the pre-loaded data.
 */
class GridTreeBuilder
{
    use Configurable;
    use Extensible;
    use Injectable;

    /** @var array<class-string, array<class-string, string>> */
    private array $allowedTypesCache = [];

    public function __construct(
        private readonly GridElementRepositoryInterface $elementRepository,
    ) {
    }

    /**
     * Build the full element tree for a page, keyed by parent ID.
     *
     * @return array<int, list<GridNode>>
     */
    public function buildForPage(SiteTree $page): array
    {
        /** @var positive-int $pageId */
        $pageId = $page->ID;

        $elementsByParent = $this->loadAllElements($pageId, $page::class);

        $rootKey = $page::class . ':' . $pageId;

        /** @var array<int, list<GridNode>> $tree */
        $tree = [];
        $tree[$pageId] = $this->assembleSubTree($elementsByParent, $rootKey, $pageId);

        return $tree;
    }

    /**
     * Breadth-first batch loading: one query per hierarchy depth level.
     *
     * Filters by both ParentID and ParentClass to avoid false matches when
     * a page ID coincides with a GridElement ID (they share no ID namespace
     * separation after the ElementalArea intermediary was removed).
     *
     * Elements are keyed by a composite "ParentClass:ParentID" string to
     * prevent collisions when a page ID equals a GridElement ID.
     *
     * @param positive-int $rootParentId
     * @param class-string $rootParentClass
     * @return array<string, list<GridElement>> Map of "ParentClass:ParentID" → elements
     */
    private function loadAllElements(int $rootParentId, string $rootParentClass): array
    {
        /** @var array<string, list<GridElement>> $elementsByParent */
        $elementsByParent = [];

        /** @var list<array{id: positive-int, class: class-string}> $pendingParents */
        $pendingParents = [['id' => $rootParentId, 'class' => $rootParentClass]];

        $maxDepth = 10;
        $depth = 0;

        while ($pendingParents !== []) {
            if (++$depth > $maxDepth) {
                break;
            }

            /** @var array<class-string, list<positive-int>> $idsByClass */
            $idsByClass = [];
            foreach ($pendingParents as $parent) {
                $idsByClass[$parent['class']] ??= [];
                $idsByClass[$parent['class']][] = $parent['id'];
            }

            $elements = $this->elementRepository->findByParents($idsByClass);

            /** @var list<array{id: positive-int, class: class-string}> $nextParents */
            $nextParents = [];

            foreach ($elements as $element) {
                $key = $element->ParentClass . ':' . $element->ParentID;
                $elementsByParent[$key] ??= [];
                $elementsByParent[$key][] = $element;

                if ($element instanceof ContainerInterface) {
                    /** @var positive-int $elementId */
                    $elementId = $element->ID;
                    $nextParents[] = ['id' => $elementId, 'class' => $element::class];
                }
            }

            $pendingParents = $nextParents;
        }

        return $elementsByParent;
    }

    /**
     * Recursively assemble tree nodes from pre-loaded element data.
     *
     * @param array<string, list<GridElement>> $elementsByParent
     * @param positive-int $parentId Numeric parent ID for the GridNode
     * @return list<GridNode>
     */
    private function assembleSubTree(array $elementsByParent, string $parentKey, int $parentId): array
    {
        $nodes = [];

        foreach ($elementsByParent[$parentKey] ?? [] as $element) {
            if (!$element->canView()) {
                continue;
            }

            $nodes[] = $this->buildElementNode($element, $elementsByParent, $parentId);
        }

        return $nodes;
    }

    /**
     * Build a single element node with base fields and optional container fields.
     *
     * @param array<string, list<GridElement>> $elementsByParent
     * @param positive-int $parentId
     */
    private function buildElementNode(GridElement $element, array $elementsByParent, int $parentId): GridNode
    {
        $containerType = null;
        $allowedTypes = null;
        $children = null;
        $gridSettings = null;

        if ($element instanceof ContainerInterface) {
            /** @var positive-int $elementId */
            $elementId = (int) $element->ID;
            $childKey = $element::class . ':' . $elementId;

            $containerType = $element->getContainerType();
            $allowedTypes = $this->getAllowedTypes($element);
            $children = $this->assembleSubTree($elementsByParent, $childKey, $elementId);
        }

        if ($element instanceof Column) {
            $gridSettings = $element->getGridSettingsData();
        }

        $id = (int) $element->ID;
        $title = $element->Title ?: _t(GridElement::class . '.UNTITLED', '(untitled)');
        $obsoleteClassName = $element->getObsoleteClassName();
        $version = (int) $element->Version;
        $canDelete = (bool) $element->canDelete();
        $canPublish = (bool) $element->canPublish();
        $canUnpublish = (bool) $element->canUnpublish();
        $canCreate = (bool) $element->canCreate();

        /** @var array{typeName: string, actions: array{edit: string}, content: string, label: string} $blockSchema */
        $blockSchema = $element->getBlockSchema();
        $blockSchema['label'] = $element->getType();

        /** @var array<string, array{text: string, title: string}> $statusFlags */
        $statusFlags = $element->getStatusFlags();

        /** @var array<string, mixed> $extensions */
        $extensions = [];
        $this->extend('updateElementData', $element, $extensions);
        /** @var array<string, mixed> $extensions PHPStan: extend() widens by-ref params */

        return new GridNode(
            id: $id,
            parentId: $parentId,
            title: $title,
            blockSchema: $blockSchema,
            obsoleteClassName: $obsoleteClassName,
            version: $version,
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
     * Reads allowed_elements / disallowed_elements config directly.
     *
     * @return array<class-string, string> FQCN → display label
     */
    private function getAllowedTypes(GridElement $container): array
    {
        $className = $container::class;

        if (isset($this->allowedTypesCache[$className])) {
            return $this->allowedTypesCache[$className];
        }

        $config = Config::forClass($className);
        $stopInheritance = (bool) $config->get('stop_element_inheritance');

        $allowedElements = $stopInheritance
            ? $config->get('allowed_elements', Config::UNINHERITED)
            : $config->get('allowed_elements');

        $disallowedElements = $stopInheritance
            ? (array) $config->get('disallowed_elements', Config::UNINHERITED)
            : (array) $config->get('disallowed_elements');

        $types = [];

        if (is_array($allowedElements)) {
            foreach ($allowedElements as $class) {
                if (is_string($class) && is_subclass_of($class, GridElement::class)) {
                    $types[$class] = $this->getElementLabel($class);
                }
            }
        } else {
            // No allowlist — all GridElement subclasses except disallowed
            foreach (ClassInfo::subclassesFor(GridElement::class, false) as $class) {
                /** @var class-string<GridElement> $class */
                if (!in_array($class, $disallowedElements, true)) {
                    $types[$class] = $this->getElementLabel($class);
                }
            }
        }

        $this->allowedTypesCache[$className] = $types;

        return $types;
    }

    /**
     * Get a human-readable label for an element class.
     *
     * @param class-string<GridElement> $class
     */
    private function getElementLabel(string $class): string
    {
        $name = Config::forClass($class)->get('singular_name');

        return is_string($name) && $name !== '' ? $name : ClassInfo::shortName($class);
    }
}
