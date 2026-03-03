<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Extensions;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;

/**
 * Tests the grid CSS class accessor methods on each container element.
 *
 * Uses the default Bootstrap adapter (wired via Injector in the test environment).
 */
#[CoversClass(ElementSection::class)]
#[CoversClass(ElementRow::class)]
#[CoversClass(ElementColumn::class)]
final class GridClassesTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Required for onAfterWrite scaffolding to run (same as ElementContainerContractTestCase)
        Versioned::set_stage(Versioned::DRAFT);
    }

    // --- Section: getContainerClasses() ---

    public function testContainerClassesForSection(): void
    {
        $section = ElementSection::create();
        $section->write();

        $this->assertSame('container', $section->getContainerClasses());
    }

    public function testFluidContainerClasses(): void
    {
        Config::modify()->set(ElementSection::class, 'fluid_container', true);

        $section = ElementSection::create();
        $section->write();

        $this->assertSame('container-fluid', $section->getContainerClasses());
    }

    // --- Row: getRowClasses() ---

    public function testRowClassesForRow(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $this->assertSame('row', $row->getRowClasses());
    }

    // --- Column: getColumnClasses() ---

    public function testColumnClassesWithDefaultSettings(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        $classes = $column->getColumnClasses();

        $this->assertStringContainsString('col-12', $classes);
        $this->assertStringContainsString('col-sm-12', $classes);
        $this->assertStringContainsString('col-md-12', $classes);
        $this->assertStringContainsString('col-lg-12', $classes);
        $this->assertStringContainsString('col-xl-12', $classes);
    }

    public function testColumnClassesWithCustomWidths(): void
    {
        $column = ElementColumn::create();
        $column->setGridSettingsData([
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 8, 'offset' => 0, 'visible' => true],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ]);
        $column->write();

        $classes = $column->getColumnClasses();

        $this->assertStringContainsString('col-md-8', $classes);
        $this->assertStringContainsString('col-lg-6', $classes);
    }

    public function testColumnClassesWithOffset(): void
    {
        $column = ElementColumn::create();
        $column->setGridSettingsData([
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 8, 'offset' => 2, 'visible' => true],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ]);
        $column->write();

        $classes = $column->getColumnClasses();

        $this->assertStringContainsString('col-md-8', $classes);
        $this->assertStringContainsString('offset-md-2', $classes);
        $this->assertStringNotContainsString('offset-lg', $classes);
    }

    public function testColumnClassesWithHiddenViewport(): void
    {
        $column = ElementColumn::create();
        $column->setGridSettingsData([
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => false],
            'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 8, 'offset' => 0, 'visible' => true],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ]);
        $column->write();

        $classes = $column->getColumnClasses();

        // Bootstrap xs hidden: d-none + d-sm-block
        $this->assertStringContainsString('d-none', $classes);
        $this->assertStringContainsString('d-sm-block', $classes);
        // Hidden viewport should not produce width classes
        $this->assertStringNotContainsString('col-12 ', $classes);
    }

    public function testColumnClassesZeroOffsetExcluded(): void
    {
        $column = ElementColumn::create();
        $column->setGridSettingsData([
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
        ]);
        $column->write();

        $classes = $column->getColumnClasses();

        $this->assertStringNotContainsString('offset', $classes);
    }
}
