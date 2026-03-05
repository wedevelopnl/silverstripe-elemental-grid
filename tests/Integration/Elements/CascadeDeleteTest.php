<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Elements;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;

/**
 * Guards cascade_deletes configuration on container elements.
 *
 * Losing cascade_deletes silently orphans child elements when a container
 * is deleted. These tests build the full Section -> Row -> Column hierarchy
 * via fixture and verify that deleting at each level removes all descendants
 * while ancestors survive.
 */
class CascadeDeleteTest extends SapphireTest
{
    protected static $fixture_file = __DIR__ . '/../Fixture/CascadeDeleteTest.yml';

    protected function setUp(): void
    {
        Section::$autoScaffold = false;
        Row::$autoScaffold = false;
        parent::setUp();
        Section::$autoScaffold = true;
        Row::$autoScaffold = true;

        Versioned::set_stage(Versioned::DRAFT);
    }

    public function testDeletingSectionDeletesEntireHierarchy(): void
    {
        $section = $this->objFromFixture(Section::class, 'section1');
        $rowId = $this->idFromFixture(Row::class, 'row1');
        $columnId = $this->idFromFixture(Column::class, 'column1');

        $section->delete();

        $this->assertNull(Row::get()->byID($rowId), 'Row should be deleted');
        $this->assertNull(Column::get()->byID($columnId), 'Column should be deleted');
    }

    public function testDeletingRowDeletesDescendants(): void
    {
        $sectionId = $this->idFromFixture(Section::class, 'section1');
        $row = $this->objFromFixture(Row::class, 'row1');
        $columnId = $this->idFromFixture(Column::class, 'column1');

        $row->delete();

        $this->assertNull(Column::get()->byID($columnId), 'Column should be deleted');

        $this->assertNotNull(Section::get()->byID($sectionId), 'Section should survive');
    }

    public function testDeletingColumnLeavesParentsIntact(): void
    {
        $sectionId = $this->idFromFixture(Section::class, 'section1');
        $rowId = $this->idFromFixture(Row::class, 'row1');
        $column = $this->objFromFixture(Column::class, 'column1');

        $column->delete();

        $this->assertNotNull(Section::get()->byID($sectionId), 'Section should survive');
        $this->assertNotNull(Row::get()->byID($rowId), 'Row should survive');
    }
}
