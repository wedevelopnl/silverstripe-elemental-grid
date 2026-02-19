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

        $body = $this->parseJsonBody($request);
        $elementClass = $body['elementClass'] ?? null;
        $elementalAreaID = $body['elementalAreaID'] ?? null;
        $afterElementID = $body['insertAfterElementID'] ?? null;

        if (!is_string($elementClass) || !is_subclass_of($elementClass, BaseElement::class)) {
            $this->jsonError(400);
        }

        if (!is_int($elementalAreaID)) {
            $this->jsonError(400);
        }

        if ($afterElementID !== null && !is_int($afterElementID)) {
            $this->jsonError(400);
        }

        /** @var ElementalArea|null $area */
        $area = ElementalArea::get()->byID($elementalAreaID);
        if ($area === null) {
            $this->jsonError(400);
        }

        if (!$area->canEdit()) {
            $this->jsonError(403);
        }

        /** @var BaseElement $newElement */
        $newElement = Injector::inst()->create($elementClass);
        if (!$newElement->canCreate()) {
            $this->jsonError(403);
        }

        $newElement->ParentID = $area->ID;
        $newElement->ensureSortSet();

        if ($afterElementID !== null) {
            $this->reorderElements($newElement, $afterElementID);
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
     * Parse the JSON request body into an associative array.
     *
     * @return array<string, mixed>
     */
    private function parseJsonBody(HTTPRequest $request): array
    {
        $data = json_decode($request->getBody() ?? '', true);

        if (!is_array($data)) {
            $this->jsonError(400);
        }

        /** @var array<string, mixed> $data JSON objects always have string keys */
        return $data;
    }

    /**
     * Extract and validate a required integer `id` from the JSON request body.
     */
    private function requireElementId(HTTPRequest $request): int
    {
        $body = $this->parseJsonBody($request);
        $id = $body['id'] ?? null;

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
