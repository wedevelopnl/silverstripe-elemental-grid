<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Adapter\BootstrapAdapter;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

#[CoversClass(BootstrapAdapter::class)]
final class BootstrapAdapterTest extends TestCase
{
    private BootstrapAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new BootstrapAdapter();
    }

    public function testImplementsGridAdapterInterface(): void
    {
        $this->assertInstanceOf(GridAdapterInterface::class, $this->adapter);
    }

    public function testIsFinalReadonlyClass(): void
    {
        $reflection = new \ReflectionClass(BootstrapAdapter::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    // ─── getViewports ────────────────────────────────────────────────

    public function testGetViewportsReturnsSixViewports(): void
    {
        $viewports = $this->adapter->getViewports();

        $this->assertCount(6, $viewports);
    }

    public function testGetViewportsReturnsViewportInstances(): void
    {
        $viewports = $this->adapter->getViewports();

        foreach ($viewports as $viewport) {
            $this->assertInstanceOf(Viewport::class, $viewport);
        }
    }

    public function testGetViewportsOrderedSmallestToLargest(): void
    {
        $viewports = $this->adapter->getViewports();
        $keys = array_map(static fn (Viewport $v): string => $v->key, $viewports);

        $this->assertSame(['xs', 'sm', 'md', 'lg', 'xl', 'xxl'], $keys);
    }

    #[DataProvider('viewportDefinitionProvider')]
    public function testViewportDefinition(int $index, string $expectedKey, string $expectedLabel): void
    {
        $viewport = $this->adapter->getViewports()[$index];

        $this->assertSame($expectedKey, $viewport->key);
        $this->assertSame($expectedLabel, $viewport->label);
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function viewportDefinitionProvider(): iterable
    {
        yield 'xs — Extra Small' => [0, 'xs', 'Extra Small'];
        yield 'sm — Small' => [1, 'sm', 'Small'];
        yield 'md — Medium' => [2, 'md', 'Medium'];
        yield 'lg — Large' => [3, 'lg', 'Large'];
        yield 'xl — Extra Large' => [4, 'xl', 'Extra Large'];
        yield 'xxl — Extra Extra Large' => [5, 'xxl', 'Extra Extra Large'];
    }

    public function testGetViewportsReturnsSameInstanceOnRepeatedCalls(): void
    {
        $first = $this->adapter->getViewports();
        $second = $this->adapter->getViewports();

        $this->assertSame($first, $second);
    }

    // ─── getColumnCount ──────────────────────────────────────────────

    public function testGetColumnCountReturnsTwelve(): void
    {
        $this->assertSame(12, $this->adapter->getColumnCount());
    }

    // ─── getDefaultViewport ──────────────────────────────────────────

    public function testGetDefaultViewportReturnsMd(): void
    {
        $default = $this->adapter->getDefaultViewport();

        $this->assertSame('md', $default->key);
        $this->assertSame('Medium', $default->label);
    }

    public function testGetDefaultViewportExistsInViewportList(): void
    {
        $default = $this->adapter->getDefaultViewport();
        $viewports = $this->adapter->getViewports();

        $this->assertContains($default, $viewports);
    }

    // ─── getWidthClass ───────────────────────────────────────────────

    #[DataProvider('widthClassProvider')]
    public function testGetWidthClass(string $viewport, int $width, string $expected): void
    {
        $this->assertSame($expected, $this->adapter->getWidthClass($viewport, $width));
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function widthClassProvider(): iterable
    {
        // xs has no viewport prefix (Bootstrap mobile-first default)
        yield 'xs full width' => ['xs', 12, 'col-12'];
        yield 'xs half width' => ['xs', 6, 'col-6'];
        yield 'xs single column' => ['xs', 1, 'col-1'];

        // All other viewports use the prefix
        yield 'sm 4 columns' => ['sm', 4, 'col-sm-4'];
        yield 'md 6 columns' => ['md', 6, 'col-md-6'];
        yield 'lg 8 columns' => ['lg', 8, 'col-lg-8'];
        yield 'xl 3 columns' => ['xl', 3, 'col-xl-3'];
        yield 'xxl 12 columns' => ['xxl', 12, 'col-xxl-12'];
    }

    // ─── getOffsetClass ──────────────────────────────────────────────

    #[DataProvider('offsetClassProvider')]
    public function testGetOffsetClass(string $viewport, int $offset, string $expected): void
    {
        $this->assertSame($expected, $this->adapter->getOffsetClass($viewport, $offset));
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function offsetClassProvider(): iterable
    {
        // xs has no viewport prefix
        yield 'xs no offset' => ['xs', 0, 'offset-0'];
        yield 'xs offset 3' => ['xs', 3, 'offset-3'];
        yield 'xs offset 6' => ['xs', 6, 'offset-6'];

        // All other viewports use the prefix
        yield 'sm offset 2' => ['sm', 2, 'offset-sm-2'];
        yield 'md offset 3' => ['md', 3, 'offset-md-3'];
        yield 'lg offset 1' => ['lg', 1, 'offset-lg-1'];
        yield 'xl offset 4' => ['xl', 4, 'offset-xl-4'];
        yield 'xxl offset 0' => ['xxl', 0, 'offset-xxl-0'];
    }

    // ─── getVisibilityClasses ────────────────────────────────────────

    #[DataProvider('visibilityClassProvider')]
    public function testGetVisibilityClasses(string $viewport, array $expected): void
    {
        $this->assertSame($expected, $this->adapter->getVisibilityClasses($viewport));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function visibilityClassProvider(): iterable
    {
        // xs: hide at xs, restore at sm
        yield 'xs — hide default, restore at sm' => ['xs', ['d-none', 'd-sm-block']];
        // sm: hide at sm, restore at md
        yield 'sm — hide at sm, restore at md' => ['sm', ['d-sm-none', 'd-md-block']];
        // md: hide at md, restore at lg
        yield 'md — hide at md, restore at lg' => ['md', ['d-md-none', 'd-lg-block']];
        // lg: hide at lg, restore at xl
        yield 'lg — hide at lg, restore at xl' => ['lg', ['d-lg-none', 'd-xl-block']];
        // xl: hide at xl, restore at xxl
        yield 'xl — hide at xl, restore at xxl' => ['xl', ['d-xl-none', 'd-xxl-block']];
        // xxl: last viewport, hide only (no next breakpoint to restore)
        yield 'xxl — hide at xxl, no restore' => ['xxl', ['d-xxl-none']];
    }

    public function testGetVisibilityClassesReturnsTwoClassesForNonLastViewport(): void
    {
        $classes = $this->adapter->getVisibilityClasses('md');

        $this->assertCount(2, $classes);
    }

    public function testGetVisibilityClassesReturnsOneClassForLastViewport(): void
    {
        $classes = $this->adapter->getVisibilityClasses('xxl');

        $this->assertCount(1, $classes);
    }

    // ─── getBaseWidthClass ──────────────────────────────────────────

    #[DataProvider('baseWidthClassProvider')]
    public function testGetBaseWidthClass(int $width, string $expected): void
    {
        $this->assertSame($expected, $this->adapter->getBaseWidthClass($width));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function baseWidthClassProvider(): iterable
    {
        yield 'single column' => [1, 'col-1'];
        yield 'half width' => [6, 'col-6'];
        yield 'full width' => [12, 'col-12'];
    }

    // ─── getBaseOffsetClass ─────────────────────────────────────────

    #[DataProvider('baseOffsetClassProvider')]
    public function testGetBaseOffsetClass(int $offset, string $expected): void
    {
        $this->assertSame($expected, $this->adapter->getBaseOffsetClass($offset));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function baseOffsetClassProvider(): iterable
    {
        yield 'no offset' => [0, 'offset-0'];
        yield 'offset 3' => [3, 'offset-3'];
        yield 'offset 11' => [11, 'offset-11'];
    }

    // ─── getRowClasses ───────────────────────────────────────────────

    public function testGetRowClassesReturnsRow(): void
    {
        $this->assertSame('row', $this->adapter->getRowClasses());
    }

    // ─── getContainerClass ───────────────────────────────────────────

    public function testGetContainerClassNonFluid(): void
    {
        $this->assertSame('container', $this->adapter->getContainerClass(false));
    }

    public function testGetContainerClassFluid(): void
    {
        $this->assertSame('container-fluid', $this->adapter->getContainerClass(true));
    }

    // ─── getTitleClassOptions ────────────────────────────────────────

    public function testGetTitleClassOptionsReturnsTwelveOptions(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertCount(12, $options);
    }

    public function testGetTitleClassOptionsKeysAreCssClasses(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        foreach (array_keys($options) as $cssClass) {
            $this->assertIsString($cssClass);
            $this->assertNotEmpty($cssClass);
        }
    }

    public function testGetTitleClassOptionsValuesAreLabels(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        foreach ($options as $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function testGetTitleClassOptionsContainsDisplayHeadings(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertArrayHasKey('display-1', $options);
        $this->assertArrayHasKey('display-2', $options);
        $this->assertArrayHasKey('display-3', $options);
        $this->assertArrayHasKey('display-4', $options);
        $this->assertArrayHasKey('display-5', $options);
        $this->assertArrayHasKey('display-6', $options);
    }

    public function testGetTitleClassOptionsContainsStandardHeadings(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertArrayHasKey('h1', $options);
        $this->assertArrayHasKey('h2', $options);
        $this->assertArrayHasKey('h3', $options);
        $this->assertArrayHasKey('h4', $options);
        $this->assertArrayHasKey('h5', $options);
        $this->assertArrayHasKey('h6', $options);
    }

    #[DataProvider('titleClassOptionProvider')]
    public function testGetTitleClassOption(string $cssClass, string $expectedLabel): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertSame($expectedLabel, $options[$cssClass]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function titleClassOptionProvider(): iterable
    {
        yield 'display-1' => ['display-1', 'Display 1'];
        yield 'display-2' => ['display-2', 'Display 2'];
        yield 'display-3' => ['display-3', 'Display 3'];
        yield 'display-4' => ['display-4', 'Display 4'];
        yield 'display-5' => ['display-5', 'Display 5'];
        yield 'display-6' => ['display-6', 'Display 6'];
        yield 'h1' => ['h1', 'Heading 1'];
        yield 'h2' => ['h2', 'Heading 2'];
        yield 'h3' => ['h3', 'Heading 3'];
        yield 'h4' => ['h4', 'Heading 4'];
        yield 'h5' => ['h5', 'Heading 5'];
        yield 'h6' => ['h6', 'Heading 6'];
    }

    // ─── getCssPath ──────────────────────────────────────────────────

    public function testGetCssPathReturnsNonNullString(): void
    {
        $path = $this->adapter->getCssPath();

        $this->assertNotNull($path);
        $this->assertIsString($path);
    }

    public function testGetCssPathContainsBootstrapReference(): void
    {
        $path = $this->adapter->getCssPath();

        $this->assertNotNull($path);
        $this->assertStringContainsString('bootstrap', strtolower($path));
    }
}
