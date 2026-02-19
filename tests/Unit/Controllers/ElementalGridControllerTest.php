<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Adapter\BootstrapAdapter;
use WeDevelop\ElementalGrid\Controllers\ElementalGridController;

#[CoversClass(ElementalGridController::class)]
final class ElementalGridControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        $this->config = ElementalGridController::buildAdapterConfig(new BootstrapAdapter());
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

    public function testEachViewportHasKeyLabelAndMinWidth(): void
    {
        foreach ($this->config['viewports'] as $viewport) {
            self::assertArrayHasKey('key', $viewport);
            self::assertArrayHasKey('label', $viewport);
            self::assertArrayHasKey('minWidth', $viewport);
            self::assertIsString($viewport['key']);
            self::assertIsString($viewport['label']);
        }
    }

    public function testViewportKeysMatchBootstrapBreakpoints(): void
    {
        $keys = array_column($this->config['viewports'], 'key');

        self::assertSame(['xs', 'sm', 'md', 'lg', 'xl', 'xxl'], $keys);
    }

    public function testFirstViewportHasNullMinWidth(): void
    {
        self::assertNull($this->config['viewports'][0]['minWidth']);
    }

    public function testNonFirstViewportsHaveIntegerMinWidth(): void
    {
        $viewports = array_slice($this->config['viewports'], 1);

        foreach ($viewports as $viewport) {
            self::assertIsInt($viewport['minWidth']);
            self::assertGreaterThan(0, $viewport['minWidth']);
        }
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

    public function testBaseWidthClassesContainsTwelveEntries(): void
    {
        self::assertCount(12, $this->config['baseWidthClasses']);
    }

    public function testBaseWidthClassesKeysRangeFrom1To12(): void
    {
        $keys = array_keys($this->config['baseWidthClasses']);

        self::assertSame(range(1, 12), $keys);
    }

    public function testBaseWidthClassesProducesUnprefixedClasses(): void
    {
        // Bootstrap xs viewport produces 'col-N' (no viewport infix)
        self::assertSame('col-1', $this->config['baseWidthClasses'][1]);
        self::assertSame('col-6', $this->config['baseWidthClasses'][6]);
        self::assertSame('col-12', $this->config['baseWidthClasses'][12]);
    }

    public function testBaseWidthClassesAllMatchBootstrapPattern(): void
    {
        foreach ($this->config['baseWidthClasses'] as $width => $class) {
            self::assertSame(sprintf('col-%d', $width), $class);
        }
    }

    // --- baseOffsetClasses ---------------------------------------------------

    public function testBaseOffsetClassesContainsTwelveEntries(): void
    {
        self::assertCount(12, $this->config['baseOffsetClasses']);
    }

    public function testBaseOffsetClassesKeysRangeFrom0To11(): void
    {
        $keys = array_keys($this->config['baseOffsetClasses']);

        self::assertSame(range(0, 11), $keys);
    }

    public function testBaseOffsetClassesProducesUnprefixedClasses(): void
    {
        // Bootstrap xs viewport produces 'offset-N' (no viewport infix)
        self::assertSame('offset-0', $this->config['baseOffsetClasses'][0]);
        self::assertSame('offset-3', $this->config['baseOffsetClasses'][3]);
        self::assertSame('offset-11', $this->config['baseOffsetClasses'][11]);
    }

    public function testBaseOffsetClassesAllMatchBootstrapPattern(): void
    {
        foreach ($this->config['baseOffsetClasses'] as $offset => $class) {
            self::assertSame(sprintf('offset-%d', $offset), $class);
        }
    }

    // --- Base viewport resolution (fallback) ---------------------------------

    public function testFallsBackToFirstViewportWhenNoneHasNullMinWidth(): void
    {
        $adapter = new class () implements \WeDevelop\ElementalGrid\Contract\GridAdapterInterface {
            public function getViewports(): array
            {
                return [
                    new \WeDevelop\ElementalGrid\Contract\Viewport('sm', 'Small', 576),
                    new \WeDevelop\ElementalGrid\Contract\Viewport('md', 'Medium', 768),
                ];
            }

            public function getColumnCount(): int
            {
                return 2;
            }

            public function getDefaultViewport(): \WeDevelop\ElementalGrid\Contract\Viewport
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

        $config = ElementalGridController::buildAdapterConfig($adapter);

        // Falls back to first viewport ('sm'), so classes use 'sm' infix
        self::assertSame('col-sm-1', $config['baseWidthClasses'][1]);
        self::assertSame('offset-sm-0', $config['baseOffsetClasses'][0]);
    }
}
