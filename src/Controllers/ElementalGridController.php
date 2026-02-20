<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Controllers;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Services\ReorderElements;
use SilverStripe\Admin\AdminController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Adapter\BootstrapAdapter;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;
use WeDevelop\ElementalGrid\Repository\ElementalAreaRepositoryInterface;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;
use WeDevelop\ElementalGrid\Service\ElementTreeBuilder;

/**
 * @phpstan-type CreateElementBody array{
 *   elementClass: class-string<BaseElement>,
 *   elementalAreaID: positive-int,
 *   insertAfterElementID: positive-int|null,
 * }
 * @phpstan-type ElementIdBody array{id: positive-int}
 * @phpstan-type AdapterConfig array{
 *   viewports: list<array{key: string, label: string, minWidth: int|null}>,
 *   defaultViewport: string,
 *   columnCount: positive-int,
 *   rowClasses: string,
 *   baseWidthClasses: \stdClass&object{
 *     '1': string, '2': string, '3': string, '4': string,
 *     '5': string, '6': string, '7': string, '8': string,
 *     '9': string, '10': string, '11': string, '12': string,
 *   },
 *   baseOffsetClasses: \stdClass&object{
 *     '0': string, '1': string, '2': string, '3': string,
 *     '4': string, '5': string, '6': string, '7': string,
 *     '8': string, '9': string, '10': string, '11': string,
 *   },
 * }
 *
 * @property ElementRepositoryInterface $elementRepository
 * @property ElementalAreaRepositoryInterface $areaRepository
 * @property ElementTreeBuilder $treeBuilder
 */
class ElementalGridController extends AdminController
{
    private static string $url_segment = 'elemental-grid';

    private static string $required_permission_codes = 'CMS_ACCESS';

    /** @var array<string, string> */
    private static array $dependencies = [
        'elementRepository' => '%$' . ElementRepositoryInterface::class,
        'areaRepository' => '%$' . ElementalAreaRepositoryInterface::class,
        'treeBuilder' => '%$' . ElementTreeBuilder::class,
    ];

    public ElementRepositoryInterface $elementRepository;

    public ElementalAreaRepositoryInterface $areaRepository;

    public ElementTreeBuilder $treeBuilder;

    /** @var array<string, string> */
    private static array $url_handlers = [
        'GET api/readTree/$PageID!' => 'apiReadTree',
        'POST api/create' => 'apiCreate',
        'POST api/publish' => 'apiPublish',
        'POST api/unpublish' => 'apiUnpublish',
        'POST api/delete' => 'apiDelete',
        'POST api/duplicate' => 'apiDuplicate',
    ];

    /** @var list<string> */
    private static array $allowed_actions = [
        'apiReadTree',
        'apiCreate',
        'apiPublish',
        'apiUnpublish',
        'apiDelete',
        'apiDuplicate',
    ];

    public function apiReadTree(HTTPRequest $request): HTTPResponse
    {
        $pageId = (int) $request->param('PageID');

        /** @var SiteTree|null $page */
        $page = Versioned::withVersionedMode(static function () use ($pageId): ?SiteTree {
            Versioned::set_stage(Versioned::DRAFT);

            return SiteTree::get()->byID($pageId);
        });

        if ($page === null) {
            $this->jsonError(404);
        }

        if (!$page->canView()) {
            $this->jsonError(403);
        }

        if (!ClassInfo::hasMethod($page, 'getElementalRelations')) {
            $this->jsonError(404);
        }

        /** @var list<string>|false $relations */
        $relations = $page->getElementalRelations(); // @phpstan-ignore method.notFound (from ElementalAreasExtension)
        if ($relations === false || $relations === []) {
            $this->jsonError(404);
        }

        $tree = $this->treeBuilder->buildForPage($page);

        return $this->jsonSuccess(200, $tree);
    }

    public function apiCreate(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $body = $this->parseCreateBody($request);

        $area = $this->areaRepository->findById($body['elementalAreaID']);
        if ($area === null) {
            $this->jsonError(400);
        }

        if (!$area->canEdit()) {
            $this->jsonError(403);
        }

        /** @var BaseElement $newElement */
        $newElement = Injector::inst()->create($body['elementClass']);
        if (!$newElement->canCreate()) {
            $this->jsonError(403);
        }

        $newElement->ParentID = $area->ID;
        $newElement->ensureSortSet();

        if ($body['insertAfterElementID'] !== null) {
            $this->reorderElements($newElement, $body['insertAfterElementID']);
        } else {
            $newElement->write();
        }

        return $this->jsonSuccess(204);
    }

    public function apiPublish(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $id = $this->requireElementId($request);

        $element = $this->elementRepository->findById($id);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canPublish()) {
            $this->jsonError(403);
        }

        $element->publishRecursive();

        return $this->jsonSuccess(204);
    }

    public function apiUnpublish(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $id = $this->requireElementId($request);

        $element = $this->elementRepository->findById($id);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canUnpublish()) {
            $this->jsonError(403);
        }

        $element->doUnpublish();

        return $this->jsonSuccess(204);
    }

    public function apiDelete(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $id = $this->requireElementId($request);

        $element = $this->elementRepository->findById($id);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canDelete()) {
            $this->jsonError(403);
        }

        $element->doArchive();

        return $this->jsonSuccess(204);
    }

    public function apiDuplicate(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $id = $this->requireElementId($request);

        $element = $this->elementRepository->findById($id);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canCreate()) {
            $this->jsonError(403);
        }

        /** @var positive-int $parentId */
        $parentId = (int) $element->ParentID;
        $area = $this->areaRepository->findById($parentId);
        if ($area === null) {
            $this->jsonError(400);
        }

        if (!$area->canEdit()) {
            $this->jsonError(403);
        }

        $clone = $element->duplicate(false);
        $clone->Title = $this->generateCopyTitle($clone->Title ?? '');
        $clone->Sort = 0;
        $area->Elements()->add($clone);

        $this->reorderElements($clone, $id);

        return $this->jsonSuccess(204);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getClientConfig(): array
    {
        /** @var array<string, mixed> $clientConfig */
        $clientConfig = parent::getClientConfig();
        $clientConfig['controllerLink'] = $this->Link();
        $clientConfig['gridAdapter'] = self::buildAdapterConfig(new BootstrapAdapter());

        return $clientConfig;
    }

    /**
     * Build the grid adapter config for frontend consumption.
     *
     * Exposed as a static method so unit tests can verify the adapter config
     * shape without requiring the full SilverStripe framework bootstrap that
     * {@see getClientConfig()} depends on via its parent class.
     *
     * @return AdapterConfig
     */
    public static function buildAdapterConfig(GridAdapterInterface $adapter): array
    {
        $viewports = $adapter->getViewports();
        $baseViewportKey = self::resolveBaseViewportKey($viewports);

        /** @var AdapterConfig['baseWidthClasses'] $baseWidthClasses */
        $baseWidthClasses = (object) self::buildBaseWidthClasses($adapter, $baseViewportKey);

        /** @var AdapterConfig['baseOffsetClasses'] $baseOffsetClasses */
        $baseOffsetClasses = (object) self::buildBaseOffsetClasses($adapter, $baseViewportKey);

        return [
            'viewports' => array_map(
                static fn (Viewport $vp): array => [
                    'key' => $vp->key,
                    'label' => $vp->label,
                    'minWidth' => $vp->minWidth,
                ],
                $viewports,
            ),
            'defaultViewport' => $adapter->getDefaultViewport()->key,
            'columnCount' => $adapter->getColumnCount(),
            'rowClasses' => $adapter->getRowClasses(),
            'baseWidthClasses' => $baseWidthClasses,
            'baseOffsetClasses' => $baseOffsetClasses,
        ];
    }

    /**
     * Parse and validate the JSON body for element creation.
     *
     * @return CreateElementBody
     */
    private function parseCreateBody(HTTPRequest $request): array
    {
        $data = json_decode($request->getBody() ?? '', true);

        if (!is_array($data)) {
            $this->jsonError(400);
        }

        $elementClass = $data['elementClass'] ?? null;
        $elementalAreaID = $data['elementalAreaID'] ?? null;
        $afterElementID = $data['insertAfterElementID'] ?? null;

        if (!is_string($elementClass) || !is_subclass_of($elementClass, BaseElement::class)) {
            $this->jsonError(400);
        }

        if (!is_int($elementalAreaID) || $elementalAreaID < 1) {
            $this->jsonError(400);
        }

        if ($afterElementID !== null && (!is_int($afterElementID) || $afterElementID < 1)) {
            $this->jsonError(400);
        }

        return [
            'elementClass' => $elementClass,
            'elementalAreaID' => $elementalAreaID,
            'insertAfterElementID' => $afterElementID,
        ];
    }

    /**
     * Extract and validate a required integer `id` from the JSON request body.
     *
     * @return positive-int
     */
    private function requireElementId(HTTPRequest $request): int
    {
        $data = json_decode($request->getBody() ?? '', true);

        if (!is_array($data)) {
            $this->jsonError(400);
        }

        $id = $data['id'] ?? null;

        if (!is_int($id) || $id < 1) {
            $this->jsonError(400);
        }

        return $id;
    }

    private function reorderElements(BaseElement $element, int $afterElementID): void
    {
        if ($afterElementID < 1) {
            $this->jsonError(400);
        }

        /** @var ReorderElements $reorderer */
        $reorderer = Injector::inst()->create(ReorderElements::class, $element);
        $reorderer->reorder($afterElementID);
    }

    /**
     * Generate a "copy" title for a duplicated element.
     *
     * "My Block" → "My Block copy"
     * "My Block copy" → "My Block copy 2"
     * "My Block copy 2" → "My Block copy 3"
     */
    private function generateCopyTitle(string $title): string
    {
        $hasCopyPattern = '/^.*(\scopy($|\s[0-9]+$))/';
        $hasNumPattern = '/^.*(\s[0-9]+$)/';

        if (preg_match($hasCopyPattern, $title, $parts) === 1) {
            $copy = $parts[1];

            if (preg_match($hasNumPattern, $copy, $numParts) === 1) {
                $num = trim($numParts[1]);
                $inc = (int) $num + 1;

                return substr($title, 0, -strlen($num)) . (string) $inc;
            }

            return $title . ' 2';
        }

        return $title . ' copy';
    }

    /**
     * Find the base viewport key — the one with null minWidth (mobile-first default).
     *
     * Falls back to the first viewport if none has null minWidth.
     *
     * @param list<Viewport> $viewports
     */
    private static function resolveBaseViewportKey(array $viewports): string
    {
        if ($viewports === []) {
            throw new \InvalidArgumentException('Adapter must define at least one viewport.');
        }

        foreach ($viewports as $viewport) {
            if ($viewport->minWidth === null) {
                return $viewport->key;
            }
        }

        return $viewports[0]->key;
    }

    /**
     * Build a map of column widths (1..columnCount) to their base CSS classes.
     *
     * Uses the base viewport (the one with null minWidth) to produce unprefixed
     * classes. For Bootstrap, this yields 'col-1' through 'col-12'.
     *
     * @return array<int, string>
     */
    private static function buildBaseWidthClasses(GridAdapterInterface $adapter, string $baseViewportKey): array
    {
        $classes = [];
        $columnCount = $adapter->getColumnCount();

        for ($width = 1; $width <= $columnCount; $width++) {
            $classes[$width] = $adapter->getWidthClass($baseViewportKey, $width);
        }

        return $classes;
    }

    /**
     * Build a map of column offsets (0..columnCount-1) to their base CSS classes.
     *
     * Uses the base viewport (the one with null minWidth) to produce unprefixed
     * classes. For Bootstrap, this yields 'offset-0' through 'offset-11'.
     *
     * @return array<int, string>
     */
    private static function buildBaseOffsetClasses(GridAdapterInterface $adapter, string $baseViewportKey): array
    {
        $classes = [];
        $columnCount = $adapter->getColumnCount();

        for ($offset = 0; $offset < $columnCount; $offset++) {
            $classes[$offset] = $adapter->getOffsetClass($baseViewportKey, $offset);
        }

        return $classes;
    }
}
