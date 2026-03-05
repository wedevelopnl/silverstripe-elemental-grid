<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Extensions;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Model\Column;
use WeDevelop\Grid\Model\Row;
use WeDevelop\Grid\Model\Section;

/**
 * Tests the grid CSS class accessor methods on each container element.
 *
 * Uses the default Bootstrap adapter (wired via Injector in the test environment).
 */
#[CoversClass(Section::class)]
#[CoversClass(Row::class)]
#[CoversClass(Column::class)]
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
        $section = Section::create();
        $section->write();

        $this->assertSame('container', $section->getContainerClasses());
    }

    public function testFluidContainerClasses(): void
    {
        Config::modify()->set(Section::class, 'fluid_container', true);

        $section = Section::create();
        $section->write();

        $this->assertSame('container-fluid', $section->getContainerClasses());
    }

    // --- Row: getRowClasses() ---

    public function testRowClassesForRow(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $this->assertSame('row', $row->getRowClasses());
    }

    // --- Column: getColumnClasses() ---

    public function testColumnClassesWithDefaultSettings(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        $classes = $column->getColumnClasses();

        $this->assertStringContainsString('col-12', $classes);
        $this->assertStringContainsString('col-sm-12', $classes);
        $this->assertStringContainsString('col-md-12', $classes);
        $this->assertStringContainsString('col-lg-12', $classes);
        $this->assertStringContainsString('col-xl-12', $classes);
    }

    public function testColumnClassesWithCustomWidths(): void
    {
        $column = Column::create();
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
        $column = Column::create();
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
        $column = Column::create();
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

    public function testColumnClassesPreserveEarlierClassesAfterHiddenViewport(): void
    {
        $column = Column::create();
        $column->setGridSettingsData([
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 8, 'offset' => 0, 'visible' => false],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ]);
        $column->write();

        $classes = $column->getColumnClasses();

        // Pre-hidden viewports produce width classes
        $this->assertStringContainsString('col-sm-12', $classes);
        // Post-hidden viewports are still processed (not broken by continue)
        $this->assertStringContainsString('col-lg-6', $classes);
        // Hidden viewport produces visibility classes
        $this->assertStringContainsString('d-md-none', $classes);
        $this->assertStringContainsString('d-lg-block', $classes);
    }

    public function testColumnClassesZeroOffsetExcluded(): void
    {
        $column = Column::create();
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
