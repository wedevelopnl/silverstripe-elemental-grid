<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Adapter\TailwindAdapter;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

#[CoversClass(TailwindAdapter::class)]
final class TailwindAdapterTest extends TestCase
{
    private TailwindAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new TailwindAdapter();
    }

    public function testImplementsGridAdapterInterface(): void
    {
        $this->assertInstanceOf(GridAdapterInterface::class, $this->adapter);
    }

    public function testIsFinalReadonlyClass(): void
    {
        $reflection = new \ReflectionClass(TailwindAdapter::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    // ── Viewports ──────────────────────────────────────────────

    public function testGetViewportsReturnsFiveViewports(): void
    {
        $viewports = $this->adapter->getViewports();

        $this->assertCount(5, $viewports);
    }

    public function testGetViewportsReturnsViewportInstances(): void
    {
        foreach ($this->adapter->getViewports() as $viewport) {
            $this->assertInstanceOf(Viewport::class, $viewport);
        }
    }

    public function testGetViewportsAreOrderedSmallestToLargest(): void
    {
        $viewports = $this->adapter->getViewports();
        $widths = array_map(
            static fn (Viewport $viewport): ?int => $viewport->minWidth,
            $viewports,
        );

        // All Tailwind viewports have a minWidth (no null/mobile-first default)
        foreach ($widths as $width) {
            $this->assertNotNull($width);
        }

        $sorted = $widths;
        sort($sorted);
        $this->assertSame($sorted, $widths);
    }

    #[DataProvider('viewportDefinitionProvider')]
    public function testViewportHasExpectedDefinition(
        int $index,
        string $expectedKey,
        string $expectedLabel,
        int $expectedMinWidth,
    ): void {
        $viewport = $this->adapter->getViewports()[$index];

        $this->assertSame($expectedKey, $viewport->key);
        $this->assertSame($expectedLabel, $viewport->label);
        $this->assertSame($expectedMinWidth, $viewport->minWidth);
    }

    /**
     * @return iterable<string, array{int, string, string, int}>
     */
    public static function viewportDefinitionProvider(): iterable
    {
        yield 'sm — 640px' => [0, 'sm', 'Small', 640];
        yield 'md — 768px' => [1, 'md', 'Medium', 768];
        yield 'lg — 1024px' => [2, 'lg', 'Large', 1024];
        yield 'xl — 1280px' => [3, 'xl', 'Extra Large', 1280];
        yield '2xl — 1536px' => [4, '2xl', '2X Large', 1536];
    }

    // ── Column count ───────────────────────────────────────────

    public function testGetColumnCountReturnsTwelve(): void
    {
        $this->assertSame(12, $this->adapter->getColumnCount());
    }

    // ── Default viewport ───────────────────────────────────────

    public function testGetDefaultViewportReturnsSm(): void
    {
        $viewport = $this->adapter->getDefaultViewport();

        $this->assertSame('sm', $viewport->key);
        $this->assertSame('Small', $viewport->label);
        $this->assertSame(640, $viewport->minWidth);
    }

    public function testGetDefaultViewportIsFirstInViewportList(): void
    {
        $default = $this->adapter->getDefaultViewport();
        $first = $this->adapter->getViewports()[0];

        $this->assertSame($first->key, $default->key);
        $this->assertSame($first->minWidth, $default->minWidth);
    }

    // ── Width classes ──────────────────────────────────────────

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
        yield 'sm, 1 column' => ['sm', 1, 'sm:col-span-1'];
        yield 'sm, 6 columns' => ['sm', 6, 'sm:col-span-6'];
        yield 'sm, 12 columns' => ['sm', 12, 'sm:col-span-12'];
        yield 'md, 4 columns' => ['md', 4, 'md:col-span-4'];
        yield 'lg, 8 columns' => ['lg', 8, 'lg:col-span-8'];
        yield 'xl, 3 columns' => ['xl', 3, 'xl:col-span-3'];
        yield '2xl, 12 columns' => ['2xl', 12, '2xl:col-span-12'];
    }

    // ── Offset classes ─────────────────────────────────────────

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
        // col-start is 1-based, so offset N → col-start-(N+1)
        yield 'sm, offset 0' => ['sm', 0, 'sm:col-start-1'];
        yield 'sm, offset 1' => ['sm', 1, 'sm:col-start-2'];
        yield 'sm, offset 3' => ['sm', 3, 'sm:col-start-4'];
        yield 'md, offset 6' => ['md', 6, 'md:col-start-7'];
        yield 'lg, offset 11' => ['lg', 11, 'lg:col-start-12'];
        yield 'xl, offset 0' => ['xl', 0, 'xl:col-start-1'];
        yield '2xl, offset 5' => ['2xl', 5, '2xl:col-start-6'];
    }

    // ── Visibility classes ─────────────────────────────────────

    #[DataProvider('visibilityClassProvider')]
    public function testGetVisibilityClasses(string $viewport, array $expected): void
    {
        $this->assertSame($expected, $this->adapter->getVisibilityClasses($viewport));
    }

    /**
     * Hiding at a viewport requires a hide class at that breakpoint plus a show
     * class at the next breakpoint to restore visibility. The last viewport has
     * no "next" so only needs the hide class.
     *
     * @return iterable<string, array{string, list<string>}>
     */
    public static function visibilityClassProvider(): iterable
    {
        yield 'sm — hide + restore at md' => ['sm', ['sm:hidden', 'md:block']];
        yield 'md — hide + restore at lg' => ['md', ['md:hidden', 'lg:block']];
        yield 'lg — hide + restore at xl' => ['lg', ['lg:hidden', 'xl:block']];
        yield 'xl — hide + restore at 2xl' => ['xl', ['xl:hidden', '2xl:block']];
        yield '2xl — last viewport, hide only' => ['2xl', ['2xl:hidden']];
    }

    // ── Row classes ────────────────────────────────────────────

    public function testGetRowClasses(): void
    {
        $this->assertSame('grid grid-cols-12', $this->adapter->getRowClasses());
    }

    // ── Container class ────────────────────────────────────────

    public function testGetContainerClassNonFluid(): void
    {
        $this->assertSame('container mx-auto', $this->adapter->getContainerClass(false));
    }

    public function testGetContainerClassFluid(): void
    {
        $this->assertSame('w-full', $this->adapter->getContainerClass(true));
    }

    // ── Title class options ────────────────────────────────────

    public function testGetTitleClassOptionsReturnsAllSixHeadings(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertCount(6, $options);
    }

    public function testGetTitleClassOptionsKeysAreCssClasses(): void
    {
        $options = $this->adapter->getTitleClassOptions();
        $expectedKeys = ['text-4xl', 'text-3xl', 'text-2xl', 'text-xl', 'text-lg', 'text-base'];

        $this->assertSame($expectedKeys, array_keys($options));
    }

    public function testGetTitleClassOptionsValuesAreHumanReadable(): void
    {
        $options = $this->adapter->getTitleClassOptions();

        foreach ($options as $class => $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            // Labels should describe heading level
            $this->assertMatchesRegularExpression('/Heading \d/', $label);
        }
    }

    #[DataProvider('titleClassOptionProvider')]
    public function testTitleClassOption(string $expectedClass, string $expectedLabel): void
    {
        $options = $this->adapter->getTitleClassOptions();

        $this->assertArrayHasKey($expectedClass, $options);
        $this->assertSame($expectedLabel, $options[$expectedClass]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function titleClassOptionProvider(): iterable
    {
        yield 'h1 equivalent' => ['text-4xl', 'Heading 1'];
        yield 'h2 equivalent' => ['text-3xl', 'Heading 2'];
        yield 'h3 equivalent' => ['text-2xl', 'Heading 3'];
        yield 'h4 equivalent' => ['text-xl', 'Heading 4'];
        yield 'h5 equivalent' => ['text-lg', 'Heading 5'];
        yield 'h6 equivalent' => ['text-base', 'Heading 6'];
    }

    // ── CSS path ───────────────────────────────────────────────

    public function testGetCssPathReturnsNull(): void
    {
        $this->assertNull($this->adapter->getCssPath());
    }
}
