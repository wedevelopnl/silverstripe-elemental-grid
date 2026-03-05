<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Controllers;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Admin\AdminController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Contract\Viewport;
use WeDevelop\Grid\Model\Result;
use WeDevelop\Grid\Model\ValidationError;
use WeDevelop\Grid\Repository\ElementalAreaRepositoryInterface;
use WeDevelop\Grid\Repository\ElementRepositoryInterface;
use WeDevelop\Grid\Service\ElementPersistenceService;
use WeDevelop\Grid\Service\ElementTreeBuilder;
use WeDevelop\Grid\Service\ReorderService;

/**
 * @phpstan-type CreateElementBody array{
 *   elementClass: class-string<BaseElement>,
 *   elementalAreaID: positive-int,
 *   insertAfterElementID: positive-int|null,
 * }
 * @phpstan-type ElementIdBody array{id: positive-int}
 * @phpstan-type ReorderBody array{
 *   elementID: positive-int,
 *   targetAreaID: positive-int,
 *   afterElementID: positive-int|null,
 * }
 * @phpstan-type AdapterConfig array{
 *   viewports: list<array{key: string, label: string}>,
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
 * @property ElementPersistenceService $persistenceService
 * @property ReorderService $reorderService
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
        'persistenceService' => '%$' . ElementPersistenceService::class,
        'reorderService' => '%$' . ReorderService::class,
        'gridAdapter' => '%$' . GridAdapterInterface::class,
    ];

    public ElementRepositoryInterface $elementRepository;

    public ElementalAreaRepositoryInterface $areaRepository;

    public ElementTreeBuilder $treeBuilder;

    public ElementPersistenceService $persistenceService;

    public ReorderService $reorderService;

    public GridAdapterInterface $gridAdapter;

    /** @var array<string, string> */
    private static array $url_handlers = [
        'GET api/readTree/$PageID!' => 'apiReadTree',
        'POST api/create' => 'apiCreate',
        'PATCH api/publish' => 'apiPublish',
        'PATCH api/unpublish' => 'apiUnpublish',
        'DELETE api/delete' => 'apiDelete',
        'POST api/duplicate' => 'apiDuplicate',
        'PATCH api/reorder' => 'apiReorder',
    ];

    /** @var list<string> */
    private static array $allowed_actions = [
        'apiReadTree',
        'apiCreate',
        'apiPublish',
        'apiUnpublish',
        'apiDelete',
        'apiDuplicate',
        'apiReorder',
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

        $result = $this->persistenceService->persistNew($newElement, $body['insertAfterElementID']);
        if ($result->isErr()) {
            return $this->resultToResponse($result);
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
        $clone->ParentID = $area->ID;

        $result = $this->persistenceService->persistDuplicate($clone, $id);
        if ($result->isErr()) {
            return $this->resultToResponse($result);
        }

        return $this->jsonSuccess(204);
    }

    public function apiReorder(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $body = $this->parseReorderBody($request);

        $element = $this->elementRepository->findById($body['elementID']);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canEdit()) {
            $this->jsonError(403);
        }

        $targetArea = $this->areaRepository->findById($body['targetAreaID']);
        if ($targetArea === null) {
            $this->jsonError(400);
        }

        if (!$targetArea->canEdit()) {
            $this->jsonError(403);
        }

        /** @var positive-int $sourceParentId */
        $sourceParentId = (int) $element->ParentID;
        $isCrossArea = $sourceParentId !== $body['targetAreaID'];

        if ($isCrossArea) {
            $sourceArea = $this->areaRepository->findById($sourceParentId);
            if ($sourceArea === null || !$sourceArea->canEdit()) {
                $this->jsonError(403);
            }
        }

        $result = $this->reorderService->reorder($element, $targetArea, $body['afterElementID']);
        if ($result->isErr()) {
            return $this->resultToResponse($result);
        }

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
        $clientConfig['gridAdapter'] = self::buildAdapterConfig($this->gridAdapter);

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

        if ($viewports === []) {
            throw new \InvalidArgumentException('Adapter must define at least one viewport.');
        }

        $columnCount = $adapter->getColumnCount();

        $widthClasses = [];
        for ($width = 1; $width <= $columnCount; $width++) {
            $widthClasses[$width] = $adapter->getBaseWidthClass($width);
        }

        $offsetClasses = [];
        for ($offset = 0; $offset < $columnCount; $offset++) {
            $offsetClasses[$offset] = $adapter->getBaseOffsetClass($offset);
        }

        /** @var AdapterConfig['baseWidthClasses'] $baseWidthClasses */
        $baseWidthClasses = (object) $widthClasses;

        /** @var AdapterConfig['baseOffsetClasses'] $baseOffsetClasses */
        $baseOffsetClasses = (object) $offsetClasses;

        return [
            'viewports' => array_map(
                static fn (Viewport $vp): array => [
                    'key' => $vp->key,
                    'label' => $vp->label,
                ],
                $viewports,
            ),
            'defaultViewport' => $adapter->getDefaultViewport()->key,
            'columnCount' => $columnCount,
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
     * Parse and validate the JSON body for element reordering.
     *
     * @return ReorderBody
     */
    private function parseReorderBody(HTTPRequest $request): array
    {
        $data = json_decode($request->getBody() ?? '', true);

        if (!is_array($data)) {
            $this->jsonError(400);
        }

        $elementID = $data['elementID'] ?? null;
        $targetAreaID = $data['targetAreaID'] ?? null;
        $afterElementID = $data['afterElementID'] ?? null;

        if (!is_int($elementID) || $elementID < 1) {
            $this->jsonError(400);
        }

        if (!is_int($targetAreaID) || $targetAreaID < 1) {
            $this->jsonError(400);
        }

        if ($afterElementID !== null && (!is_int($afterElementID) || $afterElementID < 1)) {
            $this->jsonError(400);
        }

        return [
            'elementID' => $elementID,
            'targetAreaID' => $targetAreaID,
            'afterElementID' => $afterElementID,
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

    /**
     * Convert a failed Result into a 422 JSON error response.
     *
     * @template T
     * @param Result<T> $result
     */
    private function resultToResponse(Result $result): never
    {
        $messages = array_map(
            static fn (ValidationError $error): string => $error->message,
            $result->errors(),
        );

        $this->jsonError(422, implode(' ', $messages));
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

}
