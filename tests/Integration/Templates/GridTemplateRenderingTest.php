<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Adapter\BootstrapAdapter;
use WeDevelop\Grid\Adapter\BulmaAdapter;
use WeDevelop\Grid\Adapter\TailwindAdapter;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;

/**
 * Tests the full template rendering pipeline (element -> holder -> HTML output)
 * for each grid adapter. Uses the element's controller forTemplate() to render.
 */
#[CoversClass(Section::class)]
#[CoversClass(Row::class)]
#[CoversClass(Column::class)]
final class GridTemplateRenderingTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
    }

    /**
     * Swap the active grid adapter via Injector.
     *
     * @param class-string<GridAdapterInterface> $adapterClass
     */
    private function useAdapter(string $adapterClass): void
    {
        Injector::inst()->registerService(
            new $adapterClass(),
            GridAdapterInterface::class,
        );
    }

    /** Render an element through its holder template. */
    private function render(Section|Row|Column $element): string
    {
        return $element->forTemplate();
    }

    // --- Section rendering ---

    public static function containerClassProvider(): \Generator
    {
        yield 'Bootstrap' => [BootstrapAdapter::class, 'container'];
        yield 'Tailwind' => [TailwindAdapter::class, 'container mx-auto'];
        yield 'Bulma' => [BulmaAdapter::class, 'container'];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     */
    #[DataProvider('containerClassProvider')]
    public function testSectionRendersContainerClassPerAdapter(string $adapterClass, string $expected): void
    {
        $this->useAdapter($adapterClass);

        $section = Section::create();
        $section->Title = 'Test Section';
        $section->write();

        $html = $this->render($section);

        $this->assertStringContainsString($expected, $html);
    }

    public static function fluidContainerClassProvider(): \Generator
    {
        yield 'Bootstrap' => [BootstrapAdapter::class, 'container-fluid'];
        yield 'Tailwind' => [TailwindAdapter::class, 'w-full'];
        yield 'Bulma' => [BulmaAdapter::class, 'container is-fluid'];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     */
    #[DataProvider('fluidContainerClassProvider')]
    public function testSectionRendersFluidContainerPerAdapter(string $adapterClass, string $expected): void
    {
        $this->useAdapter($adapterClass);
        Config::modify()->set(Section::class, 'fluid_container', true);

        $section = Section::create();
        $section->Title = 'Fluid Section';
        $section->write();

        $html = $this->render($section);

        $this->assertStringContainsString($expected, $html);
    }

    public function testSectionRendersSectionTag(): void
    {
        $section = Section::create();
        $section->write();

        $html = $this->render($section);

        $this->assertMatchesRegularExpression('/^<section\s/', trim($html));
    }

    public function testSectionTitleRendersWhenShowTitleTrue(): void
    {
        $section = Section::create();
        $section->Title = 'Visible Title';
        $section->ShowTitle = true;
        $section->write();

        $html = $this->render($section);

        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Visible Title', $html);
    }

    public function testSectionTitleAbsentWhenShowTitleFalse(): void
    {
        $section = Section::create();
        $section->Title = 'Hidden Title';
        $section->ShowTitle = false;
        $section->write();

        $html = $this->render($section);

        $this->assertStringNotContainsString('<h2', $html);
    }

    // --- Row rendering ---

    public static function rowClassProvider(): \Generator
    {
        yield 'Bootstrap' => [BootstrapAdapter::class, 'row'];
        yield 'Tailwind' => [TailwindAdapter::class, 'grid grid-cols-12'];
        yield 'Bulma' => [BulmaAdapter::class, 'columns is-multiline'];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     */
    #[DataProvider('rowClassProvider')]
    public function testRowRendersRowClassPerAdapter(string $adapterClass, string $expected): void
    {
        $this->useAdapter($adapterClass);

        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $html = $this->render($row);

        $this->assertStringContainsString($expected, $html);
    }

    // --- Column rendering ---

    public static function columnWidthClassProvider(): \Generator
    {
        yield 'Bootstrap' => [
            BootstrapAdapter::class,
            [
                'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'md' => ['width' => 8, 'offset' => 0, 'visible' => true],
                'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xxl' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'col-md-8',
        ];

        yield 'Tailwind' => [
            TailwindAdapter::class,
            [
                'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'md' => ['width' => 8, 'offset' => 0, 'visible' => true],
                'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
                '2xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'md:col-span-8',
        ];

        yield 'Bulma' => [
            BulmaAdapter::class,
            [
                'mobile' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'tablet' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'desktop' => ['width' => 8, 'offset' => 0, 'visible' => true],
                'widescreen' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'fullhd' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'is-8-desktop',
        ];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     * @param array<string, array{width: int, offset: int, visible: bool}> $settings
     */
    #[DataProvider('columnWidthClassProvider')]
    public function testColumnRendersWidthClassesPerAdapter(string $adapterClass, array $settings, string $expected): void
    {
        $this->useAdapter($adapterClass);

        $column = Column::create();
        $column->setGridSettingsData($settings);
        $column->write();

        $html = $this->render($column);

        $this->assertStringContainsString($expected, $html);
    }

    public static function columnOffsetClassProvider(): \Generator
    {
        yield 'Bootstrap' => [
            BootstrapAdapter::class,
            [
                'xs' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'sm' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'md' => ['width' => 6, 'offset' => 3, 'visible' => true],
                'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'xxl' => ['width' => 6, 'offset' => 0, 'visible' => true],
            ],
            'offset-md-3',
        ];

        yield 'Tailwind' => [
            TailwindAdapter::class,
            [
                'sm' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'md' => ['width' => 6, 'offset' => 3, 'visible' => true],
                'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
                '2xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
            ],
            'md:col-start-4',
        ];

        yield 'Bulma' => [
            BulmaAdapter::class,
            [
                'mobile' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'tablet' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'desktop' => ['width' => 6, 'offset' => 3, 'visible' => true],
                'widescreen' => ['width' => 6, 'offset' => 0, 'visible' => true],
                'fullhd' => ['width' => 6, 'offset' => 0, 'visible' => true],
            ],
            'is-offset-3-desktop',
        ];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     * @param array<string, array{width: int, offset: int, visible: bool}> $settings
     */
    #[DataProvider('columnOffsetClassProvider')]
    public function testColumnRendersOffsetClassesPerAdapter(string $adapterClass, array $settings, string $expected): void
    {
        $this->useAdapter($adapterClass);

        $column = Column::create();
        $column->setGridSettingsData($settings);
        $column->write();

        $html = $this->render($column);

        $this->assertStringContainsString($expected, $html);
    }

    public static function columnVisibilityClassProvider(): \Generator
    {
        yield 'Bootstrap' => [
            BootstrapAdapter::class,
            [
                'xs' => ['width' => 12, 'offset' => 0, 'visible' => false],
                'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'md' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xxl' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'd-none',
        ];

        yield 'Tailwind' => [
            TailwindAdapter::class,
            [
                'sm' => ['width' => 12, 'offset' => 0, 'visible' => false],
                'md' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
                '2xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'sm:hidden',
        ];

        yield 'Bulma' => [
            BulmaAdapter::class,
            [
                'mobile' => ['width' => 12, 'offset' => 0, 'visible' => false],
                'tablet' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'desktop' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'widescreen' => ['width' => 12, 'offset' => 0, 'visible' => true],
                'fullhd' => ['width' => 12, 'offset' => 0, 'visible' => true],
            ],
            'is-hidden-mobile',
        ];
    }

    /**
     * @param class-string<GridAdapterInterface> $adapterClass
     * @param array<string, array{width: int, offset: int, visible: bool}> $settings
     */
    #[DataProvider('columnVisibilityClassProvider')]
    public function testColumnRendersVisibilityClassesForHiddenViewport(string $adapterClass, array $settings, string $expected): void
    {
        $this->useAdapter($adapterClass);

        $column = Column::create();
        $column->setGridSettingsData($settings);
        $column->write();

        $html = $this->render($column);

        $this->assertStringContainsString($expected, $html);
    }
}
