<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Controllers;

use SilverStripe\Admin\AdminController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Service\ElementTreeBuilder;

class ElementalGridController extends AdminController
{
    private static string $url_segment = 'elemental-grid';

    private static string $required_permission_codes = 'CMS_ACCESS';

    /** @var array<string, string> */
    private static array $url_handlers = [
        'GET api/readTree/$PageID!' => 'apiReadTree',
    ];

    /** @var list<string> */
    private static array $allowed_actions = [
        'apiReadTree',
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
}
