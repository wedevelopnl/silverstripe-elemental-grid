<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\Grid\Adapter\BulmaAdapter;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Value\Viewport;
use WeDevelop\Grid\Exception\InvalidGridValueException;

#[CoversClass(BulmaAdapter::class)]
final class BulmaAdapterTest extends SapphireTest
{
    protected $usesDatabase = false;

    private BulmaAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new BulmaAdapter();
    }

    public function testImplementsGridAdapterInterface(): void
    {
        $this->assertInstanceOf(GridAdapterInterface::class, $this->adapter);
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
        yield 'mobile' => [0, 'mobile', 'Mobile'];
        yield 'tablet' => [1, 'tablet', 'Tablet'];
        yield 'desktop' => [2, 'desktop', 'Desktop'];
        yield 'widescreen' => [3, 'widescreen', 'Widescreen'];
        yield 'fullhd' => [4, 'fullhd', 'Full HD'];
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

    // ─── getVisibilityClasses ────────────────────────────────────────

    /**
     * @param list<string>|null $enabledViewports
     * @param list<string> $expectedClasses
     */
    #[DataProvider('visibilityClassProvider')]
    public function testGetVisibilityClasses(?array $enabledViewports, string $viewport, array $expectedClasses): void
    {
        if ($enabledViewports !== null) {
            Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', $enabledViewports);
            Config::modify()->set(BulmaAdapter::class, 'default_viewport', $enabledViewports[0]);
        }

        $adapter = $enabledViewports !== null ? new BulmaAdapter() : $this->adapter;

        $this->assertSame($expectedClasses, $adapter->getVisibilityClasses($viewport));
    }

    /**
     * @return iterable<string, array{list<string>|null, string, list<string>}>
     */
    public static function visibilityClassProvider(): iterable
    {
        // Full set (default) — upward-scoped hiding, no -only suffix
        yield 'all — mobile' => [null, 'mobile', ['is-hidden-mobile', 'is-block-tablet']];
        yield 'all — tablet' => [null, 'tablet', ['is-hidden-tablet', 'is-block-desktop']];
        yield 'all — desktop' => [null, 'desktop', ['is-hidden-desktop', 'is-block-widescreen']];
        yield 'all — widescreen' => [null, 'widescreen', ['is-hidden-widescreen', 'is-block-fullhd']];
        yield 'all — fullhd' => [null, 'fullhd', ['is-hidden-fullhd']];

        // [tablet, desktop, widescreen] — mobile removed
        yield '[tablet,desktop,widescreen] — tablet' => [['tablet', 'desktop', 'widescreen'], 'tablet', ['is-hidden-tablet', 'is-block-desktop']];
        yield '[tablet,desktop,widescreen] — widescreen' => [['tablet', 'desktop', 'widescreen'], 'widescreen', ['is-hidden-widescreen']];

        // [desktop, widescreen, fullhd] — mobile+tablet removed
        yield '[desktop,widescreen,fullhd] — desktop' => [['desktop', 'widescreen', 'fullhd'], 'desktop', ['is-hidden-desktop', 'is-block-widescreen']];

        // [mobile, desktop, fullhd] — gaps
        yield '[mobile,desktop,fullhd] — mobile' => [['mobile', 'desktop', 'fullhd'], 'mobile', ['is-hidden-mobile', 'is-block-desktop']];
        yield '[mobile,desktop,fullhd] — desktop' => [['mobile', 'desktop', 'fullhd'], 'desktop', ['is-hidden-desktop', 'is-block-fullhd']];
        yield '[mobile,desktop,fullhd] — fullhd' => [['mobile', 'desktop', 'fullhd'], 'fullhd', ['is-hidden-fullhd']];

        // [mobile, fullhd] — only 2
        yield '[mobile,fullhd] — mobile' => [['mobile', 'fullhd'], 'mobile', ['is-hidden-mobile', 'is-block-fullhd']];
        yield '[mobile,fullhd] — fullhd' => [['mobile', 'fullhd'], 'fullhd', ['is-hidden-fullhd']];
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
        yield 'single column' => [1, 'is-1'];
        yield 'half width' => [6, 'is-6'];
        yield 'full width' => [12, 'is-12'];
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
        yield 'offset 1' => [1, 'is-offset-1'];
        yield 'offset 6' => [6, 'is-offset-6'];
        yield 'offset 11' => [11, 'is-offset-11'];
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

    // ─── Viewport filtering ─────────────────────────────────────────

    public function testEnabledViewportsSubsetFiltersCorrectly(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', ['tablet', 'desktop', 'widescreen']);
        $adapter = new BulmaAdapter();

        $keys = array_map(static fn (Viewport $v): string => $v->key, $adapter->getViewports());

        $this->assertSame(['tablet', 'desktop', 'widescreen'], $keys);
    }

    public function testEnabledViewportsEmptyArrayThrows(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', []);

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    public function testEnabledViewportsUnknownKeyThrows(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', ['mobile', 'unknown']);

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    // ─── Column count override ──────────────────────────────────────

    public function testColumnCountOverrideReturnsConfiguredValue(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'total_columns', 16);
        $adapter = new BulmaAdapter();

        $this->assertSame(16, $adapter->getColumnCount());
    }

    public function testColumnCountOverrideZeroThrows(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'total_columns', 0);

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    public function testColumnCountOverrideNegativeThrows(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'total_columns', -4);

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    // ─── Default viewport override ──────────────────────────────────

    public function testDefaultViewportOverrideResolvesValidKey(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'default_viewport', 'widescreen');
        $adapter = new BulmaAdapter();

        $viewport = $adapter->getDefaultViewport();

        $this->assertInstanceOf(Viewport::class, $viewport);
        $this->assertSame('widescreen', $viewport->key);
    }

    public function testDefaultViewportOverrideUnknownKeyThrows(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'default_viewport', 'nonexistent');

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    public function testDefaultViewportFilteredOutWithoutOverrideThrows(): void
    {
        // Default is 'desktop', which is not in the enabled set
        Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', ['mobile', 'tablet', 'fullhd']);

        $this->expectException(InvalidGridValueException::class);
        new BulmaAdapter();
    }

    public function testDefaultViewportFilteredOutWithValidOverrideSucceeds(): void
    {
        Config::modify()->set(BulmaAdapter::class, 'enabled_viewports', ['mobile', 'tablet', 'fullhd']);
        Config::modify()->set(BulmaAdapter::class, 'default_viewport', 'tablet');
        $adapter = new BulmaAdapter();

        $this->assertSame('tablet', $adapter->getDefaultViewport()->key);
    }
}
