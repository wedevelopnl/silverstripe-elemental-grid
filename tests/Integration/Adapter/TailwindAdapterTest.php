<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\Grid\Adapter\TailwindAdapter;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Contract\Viewport;
use WeDevelop\Grid\Exception\InvalidGridValueException;

#[CoversClass(TailwindAdapter::class)]
final class TailwindAdapterTest extends SapphireTest
{
    protected $usesDatabase = false;

    private TailwindAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new TailwindAdapter();
    }

    public function testImplementsGridAdapterInterface(): void
    {
        $this->assertInstanceOf(GridAdapterInterface::class, $this->adapter);
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

    #[DataProvider('viewportDefinitionProvider')]
    public function testViewportHasExpectedDefinition(
        int $index,
        string $expectedKey,
        string $expectedLabel,
    ): void {
        $viewport = $this->adapter->getViewports()[$index];

        $this->assertSame($expectedKey, $viewport->key);
        $this->assertSame($expectedLabel, $viewport->label);
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function viewportDefinitionProvider(): iterable
    {
        yield 'sm' => [0, 'sm', 'Small'];
        yield 'md' => [1, 'md', 'Medium'];
        yield 'lg' => [2, 'lg', 'Large'];
        yield 'xl' => [3, 'xl', 'Extra Large'];
        yield '2xl' => [4, '2xl', '2X Large'];
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
    }

    public function testGetDefaultViewportIsFirstInViewportList(): void
    {
        $default = $this->adapter->getDefaultViewport();
        $first = $this->adapter->getViewports()[0];

        $this->assertSame($first->key, $default->key);
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

    /**
     * @param list<string>|null $enabledViewports
     * @param list<string> $expectedClasses
     */
    #[DataProvider('visibilityClassProvider')]
    public function testGetVisibilityClasses(?array $enabledViewports, string $viewport, array $expectedClasses): void
    {
        if ($enabledViewports !== null) {
            Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', $enabledViewports);
            Config::modify()->set(TailwindAdapter::class, 'default_viewport', $enabledViewports[0]);
        }

        $adapter = $enabledViewports !== null ? new TailwindAdapter() : $this->adapter;

        $this->assertSame($expectedClasses, $adapter->getVisibilityClasses($viewport));
    }

    /**
     * @return iterable<string, array{list<string>|null, string, list<string>}>
     */
    public static function visibilityClassProvider(): iterable
    {
        // Full set (default)
        yield 'all — sm' => [null, 'sm', ['sm:hidden', 'md:block']];
        yield 'all — md' => [null, 'md', ['md:hidden', 'lg:block']];
        yield 'all — lg' => [null, 'lg', ['lg:hidden', 'xl:block']];
        yield 'all — xl' => [null, 'xl', ['xl:hidden', '2xl:block']];
        yield 'all — 2xl' => [null, '2xl', ['2xl:hidden']];

        // [md, lg, xl] — sm removed
        yield '[md,lg,xl] — md' => [['md', 'lg', 'xl'], 'md', ['md:hidden', 'lg:block']];
        yield '[md,lg,xl] — xl' => [['md', 'lg', 'xl'], 'xl', ['xl:hidden']];

        // [sm, lg, 2xl] — gaps
        yield '[sm,lg,2xl] — sm' => [['sm', 'lg', '2xl'], 'sm', ['sm:hidden', 'lg:block']];
        yield '[sm,lg,2xl] — lg' => [['sm', 'lg', '2xl'], 'lg', ['lg:hidden', '2xl:block']];
        yield '[sm,lg,2xl] — 2xl' => [['sm', 'lg', '2xl'], '2xl', ['2xl:hidden']];
    }

    // ── Base width classes ─────────────────────────────────────

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
        yield 'single column' => [1, 'col-span-1'];
        yield 'half width' => [6, 'col-span-6'];
        yield 'full width' => [12, 'col-span-12'];
    }

    // ── Base offset classes ────────────────────────────────────

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
        yield 'no offset' => [0, 'col-start-1'];
        yield 'offset 3' => [3, 'col-start-4'];
        yield 'offset 11' => [11, 'col-start-12'];
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

    // ── Viewport filtering ─────────────────────────────────────

    public function testEnabledViewportsSubsetFiltersCorrectly(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', ['md', 'lg', 'xl']);
        Config::modify()->set(TailwindAdapter::class, 'default_viewport', 'md');
        $adapter = new TailwindAdapter();

        $keys = array_map(static fn (Viewport $v): string => $v->key, $adapter->getViewports());

        $this->assertSame(['md', 'lg', 'xl'], $keys);
    }

    public function testEnabledViewportsEmptyArrayThrows(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', []);

        $this->expectException(InvalidGridValueException::class);
        $this->expectExceptionMessage('The enabled_viewports configuration cannot be an empty array.');
        new TailwindAdapter();
    }

    public function testEnabledViewportsUnknownKeyThrows(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', ['sm', 'unknown']);

        $this->expectException(InvalidGridValueException::class);
        new TailwindAdapter();
    }

    // ── Column count override ──────────────────────────────────

    public function testColumnCountOverrideReturnsConfiguredValue(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'total_columns', 16);
        $adapter = new TailwindAdapter();

        $this->assertSame(16, $adapter->getColumnCount());
    }

    public function testColumnCountOverrideZeroThrows(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'total_columns', 0);

        $this->expectException(InvalidGridValueException::class);
        new TailwindAdapter();
    }

    public function testColumnCountOverrideNegativeThrows(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'total_columns', -4);

        $this->expectException(InvalidGridValueException::class);
        new TailwindAdapter();
    }

    // ── Default viewport override ──────────────────────────────

    public function testDefaultViewportOverrideResolvesValidKey(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'default_viewport', 'lg');
        $adapter = new TailwindAdapter();

        $viewport = $adapter->getDefaultViewport();

        $this->assertInstanceOf(Viewport::class, $viewport);
        $this->assertSame('lg', $viewport->key);
    }

    public function testDefaultViewportOverrideUnknownKeyThrows(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'default_viewport', 'nonexistent');

        $this->expectException(InvalidGridValueException::class);
        new TailwindAdapter();
    }

    public function testDefaultViewportFilteredOutWithoutOverrideThrows(): void
    {
        // Default is 'sm', which is not in the enabled set
        Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', ['md', 'lg', '2xl']);

        $this->expectException(InvalidGridValueException::class);
        new TailwindAdapter();
    }

    public function testDefaultViewportFilteredOutWithValidOverrideSucceeds(): void
    {
        Config::modify()->set(TailwindAdapter::class, 'enabled_viewports', ['md', 'lg', '2xl']);
        Config::modify()->set(TailwindAdapter::class, 'default_viewport', 'lg');
        $adapter = new TailwindAdapter();

        $this->assertSame('lg', $adapter->getDefaultViewport()->key);
    }
}
