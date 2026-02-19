<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Adapter\BulmaAdapter;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

#[CoversClass(BulmaAdapter::class)]
final class BulmaAdapterTest extends TestCase
{
    private BulmaAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new BulmaAdapter();
    }

    public function testImplementsGridAdapterInterface(): void
    {
        $this->assertInstanceOf(GridAdapterInterface::class, $this->adapter);
    }

    public function testIsFinalReadonlyClass(): void
    {
        $reflection = new \ReflectionClass(BulmaAdapter::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testGetViewportsReturnsFiveViewports(): void
    {
        $viewports = $this->adapter->getViewports();

        $this->assertCount(5, $viewports);
    }

    public function testGetViewportsReturnsViewportInstances(): void
    {
        $viewports = $this->adapter->getViewports();

        foreach ($viewports as $viewport) {
            $this->assertInstanceOf(Viewport::class, $viewport);
        }
    }

    public function testGetViewportsAreOrderedSmallestToLargest(): void
    {
        $viewports = $this->adapter->getViewports();
        $keys = array_map(
            static fn (Viewport $viewport): string => $viewport->key,
            $viewports,
        );

        $this->assertSame(['mobile', 'tablet', 'desktop', 'widescreen', 'fullhd'], $keys);
    }

    #[DataProvider('viewportDefinitionProvider')]
    public function testGetViewportsHasCorrectDefinitions(
        int $index,
        string $expectedKey,
        string $expectedLabel,
        ?int $expectedMinWidth,
    ): void {
        $viewport = $this->adapter->getViewports()[$index];

        $this->assertSame($expectedKey, $viewport->key);
        $this->assertSame($expectedLabel, $viewport->label);
        $this->assertSame($expectedMinWidth, $viewport->minWidth);
    }

    /**
     * @return iterable<string, array{int, string, string, int|null}>
     */
    public static function viewportDefinitionProvider(): iterable
    {
        yield 'mobile — no min-width (default)' => [0, 'mobile', 'Mobile', null];
        yield 'tablet — 769px' => [1, 'tablet', 'Tablet', 769];
        yield 'desktop — 1024px' => [2, 'desktop', 'Desktop', 1024];
        yield 'widescreen — 1216px' => [3, 'widescreen', 'Widescreen', 1216];
        yield 'fullhd — 1408px' => [4, 'fullhd', 'Full HD', 1408];
    }

    public function testGetColumnCountReturnsTwelve(): void
    {
        $this->assertSame(12, $this->adapter->getColumnCount());
    }

    public function testGetDefaultViewportReturnsDesktop(): void
    {
        $viewport = $this->adapter->getDefaultViewport();

        $this->assertSame('desktop', $viewport->key);
        $this->assertSame('Desktop', $viewport->label);
        $this->assertSame(1024, $viewport->minWidth);
    }

    #[DataProvider('widthClassProvider')]
    public function testGetWidthClass(string $viewport, int $width, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $this->adapter->getWidthClass($viewport, $width));
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function widthClassProvider(): iterable
    {
        // Mobile has no viewport suffix
        yield 'mobile full width' => ['mobile', 12, 'is-12'];
        yield 'mobile half width' => ['mobile', 6, 'is-6'];
        yield 'mobile single column' => ['mobile', 1, 'is-1'];

        // Other viewports include the viewport suffix
        yield 'tablet half width' => ['tablet', 6, 'is-6-tablet'];
        yield 'desktop quarter width' => ['desktop', 3, 'is-3-desktop'];
        yield 'widescreen third width' => ['widescreen', 4, 'is-4-widescreen'];
        yield 'fullhd full width' => ['fullhd', 12, 'is-12-fullhd'];
    }

    #[DataProvider('offsetClassProvider')]
    public function testGetOffsetClass(string $viewport, int $offset, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $this->adapter->getOffsetClass($viewport, $offset));
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function offsetClassProvider(): iterable
    {
        // Mobile has no viewport suffix
        yield 'mobile offset 1' => ['mobile', 1, 'is-offset-1'];
        yield 'mobile offset 6' => ['mobile', 6, 'is-offset-6'];

        // Other viewports include the viewport suffix
        yield 'tablet offset 3' => ['tablet', 3, 'is-offset-3-tablet'];
        yield 'desktop offset 4' => ['desktop', 4, 'is-offset-4-desktop'];
        yield 'widescreen offset 2' => ['widescreen', 2, 'is-offset-2-widescreen'];
        yield 'fullhd offset 11' => ['fullhd', 11, 'is-offset-11-fullhd'];
    }

    #[DataProvider('visibilityClassProvider')]
    public function testGetVisibilityClasses(string $viewport, array $expectedClasses): void
    {
        $this->assertSame($expectedClasses, $this->adapter->getVisibilityClasses($viewport));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function visibilityClassProvider(): iterable
    {
        // First viewport: hide at mobile, restore at tablet
        yield 'mobile — hide + restore at tablet' => ['mobile', ['is-hidden-mobile', 'is-block-tablet']];

        // Middle viewports: hide with -only suffix, restore at next viewport
        yield 'tablet — hide only + restore at desktop' => ['tablet', ['is-hidden-tablet-only', 'is-block-desktop']];
        yield 'desktop — hide only + restore at widescreen' => ['desktop', ['is-hidden-desktop-only', 'is-block-widescreen']];
        yield 'widescreen — hide only + restore at fullhd' => ['widescreen', ['is-hidden-widescreen-only', 'is-block-fullhd']];

        // Last viewport: just hide, nothing above it
        yield 'fullhd — hide only' => ['fullhd', ['is-hidden-fullhd']];
    }

    public function testGetRowClasses(): void
    {
        $this->assertSame('columns is-multiline', $this->adapter->getRowClasses());
    }

    public function testGetContainerClassNonFluid(): void
    {
        $this->assertSame('container', $this->adapter->getContainerClass(false));
    }

    public function testGetContainerClassFluid(): void
    {
        $this->assertSame('container is-fluid', $this->adapter->getContainerClass(true));
    }

    public function testGetTitleClassOptionsReturnsSixOptions(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertCount(6, $options);
    }

    #[DataProvider('titleClassProvider')]
    public function testGetTitleClassOptions(string $cssClass, string $expectedLabel): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertArrayHasKey($cssClass, $options);
        $this->assertSame($expectedLabel, $options[$cssClass]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function titleClassProvider(): iterable
    {
        yield 'is-1 — Title 1' => ['is-1', 'Title 1'];
        yield 'is-2 — Title 2' => ['is-2', 'Title 2'];
        yield 'is-3 — Title 3' => ['is-3', 'Title 3'];
        yield 'is-4 — Title 4' => ['is-4', 'Title 4'];
        yield 'is-5 — Title 5' => ['is-5', 'Title 5'];
        yield 'is-6 — Title 6' => ['is-6', 'Title 6'];
    }

    public function testGetCssPathReturnsNonNullString(): void
    {
        $path = $this->adapter->getCssPath();

        $this->assertNotNull($path);
        $this->assertIsString($path);
    }

    public function testGetCssPathContainsBulmaReference(): void
    {
        $path = $this->adapter->getCssPath();

        $this->assertNotNull($path);
        $this->assertStringContainsString('bulma', strtolower($path));
    }

    public function testGetDefaultViewportExistsInViewportList(): void
    {
        $defaultViewport = $this->adapter->getDefaultViewport();
        $viewportKeys = array_map(
            static fn (Viewport $viewport): string => $viewport->key,
            $this->adapter->getViewports(),
        );

        $this->assertContains($defaultViewport->key, $viewportKeys);
    }

    public function testGetViewportsReturnsSameInstancesOnRepeatedCalls(): void
    {
        $first = $this->adapter->getViewports();
        $second = $this->adapter->getViewports();

        $this->assertSame($first, $second);
    }
}
