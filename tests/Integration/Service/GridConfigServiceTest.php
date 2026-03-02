<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\ElementalGrid\Adapter\BootstrapAdapter;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\GridConfigServiceInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;
use WeDevelop\ElementalGrid\Exception\InvalidGridValueException;
use WeDevelop\ElementalGrid\Service\GridConfigService;

#[CoversClass(GridConfigService::class)]
final class GridConfigServiceTest extends SapphireTest
{
    protected $usesDatabase = false;

    private GridConfigService $service;

    private BootstrapAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new BootstrapAdapter();
        $this->service = new GridConfigService($this->adapter);
    }

    // ---- Delegation (no overrides) ----

    public function testGetViewportsDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getViewports(),
            $this->service->getViewports(),
        );
    }

    public function testGetColumnCountDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getColumnCount(),
            $this->service->getColumnCount(),
        );
    }

    public function testGetDefaultViewportDelegatesToAdapter(): void
    {
        $this->assertEquals(
            $this->adapter->getDefaultViewport(),
            $this->service->getDefaultViewport(),
        );
    }

    public function testGetWidthClassDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getWidthClass('md', 6),
            $this->service->getWidthClass('md', 6),
        );
    }

    public function testGetOffsetClassDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getOffsetClass('md', 3),
            $this->service->getOffsetClass('md', 3),
        );
    }

    public function testGetVisibilityClassesDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getVisibilityClasses('md'),
            $this->service->getVisibilityClasses('md'),
        );
    }

    public function testGetRowClassesDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getRowClasses(),
            $this->service->getRowClasses(),
        );
    }

    public function testGetContainerClassDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getContainerClass(false),
            $this->service->getContainerClass(false),
        );

        $this->assertSame(
            $this->adapter->getContainerClass(true),
            $this->service->getContainerClass(true),
        );
    }

    public function testGetTitleClassOptionsDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getTitleClassOptions(),
            $this->service->getTitleClassOptions(),
        );
    }

    public function testGetCssPathDelegatesToAdapter(): void
    {
        $this->assertSame(
            $this->adapter->getCssPath(),
            $this->service->getCssPath(),
        );
    }

    // ---- Column count override ----

    public function testColumnCountOverrideReturnsConfiguredValue(): void
    {
        Config::modify()->set(GridConfigService::class, 'total_columns', 16);

        $this->assertSame(16, $this->service->getColumnCount());
    }

    public function testColumnCountOverrideZeroThrows(): void
    {
        Config::modify()->set(GridConfigService::class, 'total_columns', 0);

        $this->expectException(InvalidGridValueException::class);
        $this->service->getColumnCount();
    }

    public function testColumnCountOverrideNegativeThrows(): void
    {
        Config::modify()->set(GridConfigService::class, 'total_columns', -4);

        $this->expectException(InvalidGridValueException::class);
        $this->service->getColumnCount();
    }

    // ---- Default viewport override ----

    public function testDefaultViewportOverrideResolvesValidKey(): void
    {
        Config::modify()->set(GridConfigService::class, 'default_viewport', 'lg');

        $viewport = $this->service->getDefaultViewport();

        $this->assertInstanceOf(Viewport::class, $viewport);
        $this->assertSame('lg', $viewport->key);
    }

    public function testDefaultViewportOverrideUnknownKeyThrows(): void
    {
        Config::modify()->set(GridConfigService::class, 'default_viewport', 'nonexistent');

        $this->expectException(InvalidGridValueException::class);
        $this->service->getDefaultViewport();
    }

    // ---- Injector wiring ----

    public function testInjectorResolvesInterface(): void
    {
        $instance = Injector::inst()->get(GridConfigServiceInterface::class);

        $this->assertInstanceOf(GridConfigService::class, $instance);
    }

    public function testInjectorResolvesAdapter(): void
    {
        $instance = Injector::inst()->get(GridAdapterInterface::class);

        $this->assertInstanceOf(BootstrapAdapter::class, $instance);
    }
}
