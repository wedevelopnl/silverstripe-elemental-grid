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
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
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

        $elementsByParent = $this->loadAllElements($pageId);

        if ($elementsByParent === []) {
            return [];
        }

        /** @var array<int, list<GridNode>> $tree */
        $tree = [];
        $tree[$pageId] = $this->assembleSubTree($elementsByParent, $pageId);

        return $tree;
    }

    /**
     * Breadth-first batch loading: one query per hierarchy depth level.
     *
     * @param positive-int $rootParentId
     * @return array<int, list<GridElement>> Map of parent ID → elements under that parent
     */
    private function loadAllElements(int $rootParentId): array
    {
        /** @var array<int, list<GridElement>> $elementsByParent */
        $elementsByParent = [];
        $pendingParentIds = [$rootParentId];

        while ($pendingParentIds !== []) {
            $elements = $this->elementRepository->findByParentIds($pendingParentIds);

            $nextParentIds = [];

            foreach ($elements as $element) {
                $parentId = (int) $element->ParentID;
                $elementsByParent[$parentId] ??= [];
                $elementsByParent[$parentId][] = $element;

                if ($element instanceof ContainerInterface) {
                    /** @var positive-int $elementId */
                    $elementId = $element->ID;
                    $nextParentIds[] = $elementId;
                }
            }

            $pendingParentIds = $nextParentIds;
        }

        return $elementsByParent;
    }

    /**
     * Recursively assemble tree nodes from pre-loaded element data.
     *
     * @param array<int, list<GridElement>> $elementsByParent
     * @param positive-int $parentId
     * @return list<GridNode>
     */
    private function assembleSubTree(array $elementsByParent, int $parentId): array
    {
        $nodes = [];

        foreach ($elementsByParent[$parentId] ?? [] as $element) {
            if (!$element->canView()) {
                continue;
            }

            $node = $this->buildElementNode($element, $elementsByParent, $parentId);
            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Build a single element node with base fields and optional container fields.
     *
     * @param array<int, list<GridElement>> $elementsByParent
     * @param positive-int $parentId
     */
    private function buildElementNode(GridElement $element, array $elementsByParent, int $parentId): ?GridNode
    {
        $containerType = null;
        $allowedTypes = null;
        $children = null;
        $gridSettings = null;

        if ($element instanceof ContainerInterface) {
            /** @var positive-int $elementId */
            $elementId = (int) $element->ID;

            $containerType = $element->getContainerType();
            $allowedTypes = $this->getAllowedTypes($element);
            $children = $this->assembleSubTree($elementsByParent, $elementId);
        }

        if ($element instanceof Column) {
            $gridSettings = $element->getGridSettingsData();
        }

        $id = (int) $element->ID;
        $title = $element->Title ?: _t(GridElement::class . '.UNTITLED', '(untitled)');
        $obsoleteClassName = $element->getObsoleteClassName(); // @phpstan-ignore method.notFound (from Versioned)
        $version = (int) $element->Version; // @phpstan-ignore cast.int (from Versioned)
        $canDelete = $element->canDelete();
        $canPublish = $element->canPublish(); // @phpstan-ignore method.notFound (from Versioned)
        $canUnpublish = (bool) $element->canUnpublish(); // @phpstan-ignore method.notFound (from Versioned)
        $canCreate = $element->canCreate();

        /** @var array{typeName: string, actions: array{edit: string}, content: string, label: string} $blockSchema */
        $blockSchema = $element->getBlockSchema();
        $blockSchema['label'] = $element->getType();

        /** @var array<string, array{text: string, title: string}> $statusFlags */
        $statusFlags = $element->getStatusFlags(); // @phpstan-ignore method.notFound (from Versioned)

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
        return (string) Config::forClass($class)->get('singular_name') ?: ClassInfo::shortName($class);
    }
}
