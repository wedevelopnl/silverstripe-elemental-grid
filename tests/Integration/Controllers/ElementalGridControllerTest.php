<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Controllers;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Controllers\ElementalGridController;
use WeDevelop\ElementalGrid\Service\ElementTreeBuilder;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

#[CoversClass(ElementalGridController::class)]
final class ElementalGridControllerTest extends FunctionalTest
{
    protected static $fixture_file = __DIR__ . '/../Fixture/ElementTreeTest.yml';

    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
        ],
    ];

    private function apiUrl(int $pageId): string
    {
        return '/admin/elemental-grid/api/readTree/' . $pageId;
    }

    /**
     * Authenticate via the FunctionalTest HTTP session (not just the in-memory identity store).
     *
     * SapphireTest::logInWithPermission() writes to the current Controller's session,
     * which is a different Session object than FunctionalTest::mainSession. Admin routes
     * authenticate via the HTTP session, so we must set 'loggedInAs' on the test session.
     *
     * Uses CMS_ACCESS_LeftAndMain because SilverStripe's Permission::checkMember() treats
     * 'CMS_ACCESS' as a category check — it matches CMS_ACCESS_* codes, not a literal
     * 'CMS_ACCESS' permission record.
     */
    private function logInForHttp(string $permissionCode = 'CMS_ACCESS_LeftAndMain'): void
    {
        $memberId = $this->logInWithPermission($permissionCode);
        $this->session()->set('loggedInAs', $memberId);
    }

    /**
     * Assert that a response is a JSON error with the expected status code and message.
     */
    private function assertJsonError(int $expectedCode, string $expectedMessage, mixed $response): void
    {
        $this->assertSame($expectedCode, $response->getStatusCode());

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('error', $body['status']);
        $this->assertCount(1, $body['errors']);
        $this->assertSame('error', $body['errors'][0]['type']);
        $this->assertSame($expectedCode, $body['errors'][0]['code']);
        $this->assertSame($expectedMessage, $body['errors'][0]['value']);
    }

    public function testReadTreeReturnsJsonForValidPage(): void
    {
        $this->logInForHttp();
        Versioned::set_stage(Versioned::DRAFT);

        $page = $this->objFromFixture(TestPage::class, 'testpage');
        $response = $this->get($this->apiUrl($page->ID));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('ElementalArea', $body);
        $this->assertNotEmpty($body['ElementalArea']);

        // Verify nested structure exists
        $firstSection = $body['ElementalArea'][0];
        $this->assertSame('First Section', $firstSection['title']);
        $this->assertArrayHasKey('children', $firstSection);
    }

    public function testReadTreeReturns404ForMissingPage(): void
    {
        $this->logInForHttp();

        $response = $this->get($this->apiUrl(999999));

        $this->assertJsonError(
            404,
            "Sorry, it seems you were trying to access a section or object that doesn't exist.",
            $response,
        );
    }

    public function testReadTreeReturns403WithoutPermission(): void
    {
        $page = $this->objFromFixture(TestPage::class, 'testpage');

        // Not logged in — AdminController redirects to login or returns 403
        $this->autoFollowRedirection = false;
        $response = $this->get($this->apiUrl($page->ID));

        // AdminController handles unauthenticated access before our action runs.
        // Depending on context it returns 302 (redirect to login) or 403.
        $this->assertTrue(
            \in_array($response->getStatusCode(), [302, 403], true),
            'Expected 302 or 403, got ' . $response->getStatusCode(),
        );
    }

    public function testReadTreeReturns404ForPageWithoutElementalRelations(): void
    {
        $this->logInForHttp();

        // SiteTree without ElementalPageExtension has no getElementalRelations()
        $page = SiteTree::create();
        $page->Title = 'Non-Elemental Page';
        $page->write();

        $response = $this->get($this->apiUrl($page->ID));

        $this->assertJsonError(
            404,
            "Sorry, it seems you were trying to access a section or object that doesn't exist.",
            $response,
        );
    }

    public function testReadTreeFindsPageRegardlessOfAmbientStage(): void
    {
        $this->logInForHttp();

        // Load page ID while in DRAFT (the page is only on draft stage)
        $pageId = Versioned::withVersionedMode(function (): int {
            Versioned::set_stage(Versioned::DRAFT);

            return $this->objFromFixture(TestPage::class, 'testpage')->ID;
        });

        // Set ambient stage to LIVE — the controller must internally switch to DRAFT
        Versioned::set_stage(Versioned::LIVE);

        $response = $this->get($this->apiUrl($pageId));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResponseMatchesTreeBuilderOutput(): void
    {
        $this->logInForHttp();
        Versioned::set_stage(Versioned::DRAFT);

        $page = $this->objFromFixture(TestPage::class, 'testpage');

        // Build tree directly
        $tree = ElementTreeBuilder::create()->buildForPage($page);
        $expected = json_decode(
            json_encode($tree, JSON_THROW_ON_ERROR),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        // Fetch via API
        $response = $this->get($this->apiUrl($page->ID));
        $actual = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($expected, $actual);
    }
}
