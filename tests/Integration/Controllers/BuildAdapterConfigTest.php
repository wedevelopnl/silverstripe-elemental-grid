<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\Grid\Adapter\BootstrapAdapter;
use WeDevelop\Grid\Controllers\GridController;

/**
 * Tests {@see GridController::buildAdapterConfig()} in isolation.
 *
 * Requires the SilverStripe config system because adapters now use
 * the Configurable trait (instantiation reads from config).
 */
#[CoversClass(GridController::class)]
final class BuildAdapterConfigTest extends SapphireTest
{
    protected $usesDatabase = false;

    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = GridController::buildAdapterConfig(new BootstrapAdapter());
    }

    // --- Structure -----------------------------------------------------------

    public function testConfigIncludesAllRequiredKeys(): void
    {
        self::assertArrayHasKey('viewports', $this->config);
        self::assertArrayHasKey('defaultViewport', $this->config);
        self::assertArrayHasKey('columnCount', $this->config);
        self::assertArrayHasKey('rowClasses', $this->config);
        self::assertArrayHasKey('baseWidthClasses', $this->config);
        self::assertArrayHasKey('baseOffsetClasses', $this->config);
    }

    public function testConfigContainsExactlySixKeys(): void
    {
        self::assertCount(6, $this->config);
    }

    // --- viewports -----------------------------------------------------------

    public function testViewportsContainsSixEntries(): void
    {
        self::assertCount(6, $this->config['viewports']);
    }

    public function testEachViewportHasKeyAndLabel(): void
    {
        foreach ($this->config['viewports'] as $viewport) {
            self::assertArrayHasKey('key', $viewport);
            self::assertArrayHasKey('label', $viewport);
            self::assertIsString($viewport['key']);
            self::assertIsString($viewport['label']);
        }
    }

    public function testViewportKeysMatchBootstrapBreakpoints(): void
    {
        $keys = array_column($this->config['viewports'], 'key');

        self::assertSame(['xs', 'sm', 'md', 'lg', 'xl', 'xxl'], $keys);
    }

    // --- defaultViewport -----------------------------------------------------

    public function testDefaultViewportIsMd(): void
    {
        self::assertSame('md', $this->config['defaultViewport']);
    }

    public function testDefaultViewportExistsInViewportList(): void
    {
        $keys = array_column($this->config['viewports'], 'key');

        self::assertContains($this->config['defaultViewport'], $keys);
    }

    // --- columnCount ---------------------------------------------------------

    public function testColumnCountIsTwelve(): void
    {
        self::assertSame(12, $this->config['columnCount']);
    }

    // --- rowClasses ----------------------------------------------------------

    public function testRowClassesIsRow(): void
    {
        self::assertSame('row', $this->config['rowClasses']);
    }

    // --- baseWidthClasses ----------------------------------------------------

    public function testBaseWidthClassesIsStdClass(): void
    {
        self::assertInstanceOf(\stdClass::class, $this->config['baseWidthClasses']);
    }

    public function testBaseWidthClassesContainsTwelveEntries(): void
    {
        $props = get_object_vars($this->config['baseWidthClasses']);

        self::assertCount(12, $props);
    }

    public function testBaseWidthClassesKeysRangeFrom1To12(): void
    {
        $keys = array_keys(get_object_vars($this->config['baseWidthClasses']));

        self::assertSame(range(1, 12), $keys);
    }

    public function testBaseWidthClassesProducesUnprefixedClasses(): void
    {
        $classes = $this->config['baseWidthClasses'];

        self::assertSame('col-1', $classes->{'1'});
        self::assertSame('col-6', $classes->{'6'});
        self::assertSame('col-12', $classes->{'12'});
    }

    public function testBaseWidthClassesAllMatchBootstrapPattern(): void
    {
        foreach ($this->config['baseWidthClasses'] as $width => $class) {
            self::assertSame(sprintf('col-%s', $width), $class);
        }
    }

    // --- baseOffsetClasses ---------------------------------------------------

    public function testBaseOffsetClassesIsStdClass(): void
    {
        self::assertInstanceOf(\stdClass::class, $this->config['baseOffsetClasses']);
    }

    public function testBaseOffsetClassesContainsTwelveEntries(): void
    {
        $props = get_object_vars($this->config['baseOffsetClasses']);

        self::assertCount(12, $props);
    }

    public function testBaseOffsetClassesKeysRangeFrom0To11(): void
    {
        $keys = array_keys(get_object_vars($this->config['baseOffsetClasses']));

        self::assertSame(range(0, 11), $keys);
    }

    public function testBaseOffsetClassesProducesUnprefixedClasses(): void
    {
        $classes = $this->config['baseOffsetClasses'];

        self::assertSame('offset-0', $classes->{'0'});
        self::assertSame('offset-3', $classes->{'3'});
        self::assertSame('offset-11', $classes->{'11'});
    }

    public function testBaseOffsetClassesAllMatchBootstrapPattern(): void
    {
        foreach ($this->config['baseOffsetClasses'] as $offset => $class) {
            self::assertSame(sprintf('offset-%s', $offset), $class);
        }
    }

    // --- JSON serialization boundary -----------------------------------------

    public function testBaseClassesSurviveJsonRoundTrip(): void
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode(json_encode($this->config), true);

        self::assertSame(
            array_keys(get_object_vars($this->config['baseWidthClasses'])),
            array_keys($decoded['baseWidthClasses']),
        );
        self::assertSame(
            array_keys(get_object_vars($this->config['baseOffsetClasses'])),
            array_keys($decoded['baseOffsetClasses']),
        );
    }

    // --- Base classes use adapter methods directly ----------------------------

    public function testBaseClassesUseAdapterBaseClassMethods(): void
    {
        $adapter = new class () implements \WeDevelop\Grid\Contract\GridAdapterInterface {
            public function getViewports(): array
            {
                return [
                    new \WeDevelop\Grid\Value\Viewport('sm', 'Small'),
                    new \WeDevelop\Grid\Value\Viewport('md', 'Medium'),
                ];
            }

            public function getColumnCount(): int
            {
                return 2;
            }

            public function getDefaultViewport(): \WeDevelop\Grid\Value\Viewport
            {
                return $this->getViewports()[0];
            }

            public function getWidthClass(string $viewport, int $width): string
            {
                return sprintf('col-%s-%d', $viewport, $width);
            }

            public function getOffsetClass(string $viewport, int $offset): string
            {
                return sprintf('offset-%s-%d', $viewport, $offset);
            }

            public function getBaseWidthClass(int $width): string
            {
                return sprintf('base-w-%d', $width);
            }

            public function getBaseOffsetClass(int $offset): string
            {
                return sprintf('base-o-%d', $offset);
            }

            public function getVisibilityClasses(string $viewport): array
            {
                return [];
            }

            public function getRowClasses(): string
            {
                return 'row';
            }

            public function getContainerClass(bool $fluid): string
            {
                return 'container';
            }

            public function getTitleClassOptions(): array
            {
                return [];
            }

            public function getCssPath(): ?string
            {
                return null;
            }
        };

        $config = GridController::buildAdapterConfig($adapter);

        self::assertSame('base-w-1', $config['baseWidthClasses']->{'1'});
        self::assertSame('base-w-2', $config['baseWidthClasses']->{'2'});
        self::assertSame('base-o-0', $config['baseOffsetClasses']->{'0'});
        self::assertSame('base-o-1', $config['baseOffsetClasses']->{'1'});
    }

    public function testThrowsOnEmptyViewportList(): void
    {
        $adapter = new class () implements \WeDevelop\Grid\Contract\GridAdapterInterface {
            public function getViewports(): array
            {
                return [];
            }

            public function getColumnCount(): int
            {
                return 12;
            }

            public function getDefaultViewport(): \WeDevelop\Grid\Value\Viewport
            {
                return new \WeDevelop\Grid\Value\Viewport('xs', 'XS');
            }

            public function getWidthClass(string $viewport, int $width): string
            {
                return '';
            }

            public function getOffsetClass(string $viewport, int $offset): string
            {
                return '';
            }

            public function getBaseWidthClass(int $width): string
            {
                return '';
            }

            public function getBaseOffsetClass(int $offset): string
            {
                return '';
            }

            public function getVisibilityClasses(string $viewport): array
            {
                return [];
            }

            public function getRowClasses(): string
            {
                return 'row';
            }

            public function getContainerClass(bool $fluid): string
            {
                return 'container';
            }

            public function getTitleClassOptions(): array
            {
                return [];
            }

            public function getCssPath(): ?string
            {
                return null;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adapter must define at least one viewport.');

        GridController::buildAdapterConfig($adapter);
    }
}
