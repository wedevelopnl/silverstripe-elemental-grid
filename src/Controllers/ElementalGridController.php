<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Controllers;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Services\ReorderElements;
use SilverStripe\Admin\AdminController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Service\ElementTreeBuilder;

/**
 * @phpstan-type CreateElementBody array{
 *   elementClass: class-string<BaseElement>,
 *   elementalAreaID: positive-int,
 *   insertAfterElementID: positive-int|null,
 * }
 * @phpstan-type ElementIdBody array{id: positive-int}
 */
class ElementalGridController extends AdminController
{
    private static string $url_segment = 'elemental-grid';

    private static string $required_permission_codes = 'CMS_ACCESS';

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

        $tree = ElementTreeBuilder::create()->buildForPage($page);

        return $this->jsonSuccess(200, $tree);
    }

    public function apiCreate(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }

        $body = $this->parseCreateBody($request);

        /** @var ElementalArea|null $area */
        $area = ElementalArea::get()->byID($body['elementalAreaID']);
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

        /** @var BaseElement|null $element */
        $element = BaseElement::get()->byID($id);
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

        /** @var BaseElement|null $element */
        $element = BaseElement::get()->byID($id);
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

        /** @var BaseElement|null $element */
        $element = BaseElement::get()->byID($id);
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

        /** @var BaseElement|null $element */
        $element = BaseElement::get()->byID($id);
        if ($element === null) {
            $this->jsonError(400);
        }

        if (!$element->canCreate()) {
            $this->jsonError(403);
        }

        /** @var ElementalArea|null $area */
        $area = ElementalArea::get()->byID($element->ParentID);
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

        return $clientConfig;
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
        if ($afterElementID < 0) {
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
}
