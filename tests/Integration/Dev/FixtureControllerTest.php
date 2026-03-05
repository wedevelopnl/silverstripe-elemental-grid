<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Dev;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Dev\FunctionalTest;
use WeDevelop\Grid\Dev\FixtureController;
use WeDevelop\Grid\Dev\FixtureLoader;

#[CoversClass(FixtureController::class)]
final class FixtureControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    private const BASE_URL = '/dev/elemental-grid-fixtures';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        FixtureLoader::config()->set('fixtures', [
            'element-tree' => 'wedevelopnl/silverstripe-elemental-grid:tests/E2E/Fixture/ElementTree.yml',
        ]);
    }

    public function testLoadReturnsJsonWithPageId(): void
    {
        $response = $this->post(self::BASE_URL . '/load', ['fixture' => 'element-tree']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($body['success']);
        $this->assertSame('element-tree', $body['fixture']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('pageId', $body['data']);
        $this->assertGreaterThan(0, $body['data']['pageId']);
        $this->assertArrayHasKey('pageUrl', $body['data']);
        $this->assertArrayHasKey('fixtureMap', $body['data']);
    }

    public function testLoadReturns400ForUnknownFixture(): void
    {
        $response = $this->post(self::BASE_URL . '/load', ['fixture' => 'nonexistent']);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Unknown fixture', $body['error']);
    }

    public function testLoadReturns400WithoutFixtureParam(): void
    {
        $response = $this->post(self::BASE_URL . '/load', []);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Missing required', $body['error']);
    }

    public function testResetReturnsSuccess(): void
    {
        // Load first to have something to reset
        $this->post(self::BASE_URL . '/load', ['fixture' => 'element-tree']);

        $response = $this->post(self::BASE_URL . '/reset', []);

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($body['success']);
    }

    /**
     * Verifies canInit() returns true in dev — the gate DevelopmentAdmin
     * checks before exposing the route. The full non-dev HTTP test is not
     * feasible because SilverStripe's test Kernel forces environment=dev.
     */
    public function testCanInitReturnsTrueInDevEnvironment(): void
    {
        $controller = FixtureController::create();

        $this->assertTrue(
            $controller->canInit(),
            'canInit() should return true in dev environment',
        );
    }
}
