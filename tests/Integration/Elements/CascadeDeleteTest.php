<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Elements;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;

/**
 * Guards cascade_deletes configuration on container elements.
 *
 * Losing cascade_deletes silently orphans ElementalArea rows (and all
 * their children) when a container is deleted. These tests build the
 * full Section -> Row -> Column hierarchy via fixture and verify that
 * deleting at each level removes all descendants while ancestors survive.
 */
class CascadeDeleteTest extends SapphireTest
{
    protected static string $fixture_file = __DIR__ . '/../Fixture/CascadeDeleteTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
    }

    public function testDeletingSectionDeletesEntireHierarchy(): void
    {
        $section = $this->objFromFixture(ElementSection::class, 'section1');
        $sectionChildAreaId = $this->idFromFixture(ElementalArea::class, 'section_child_area');
        $rowId = $this->idFromFixture(ElementRow::class, 'row1');
        $rowChildAreaId = $this->idFromFixture(ElementalArea::class, 'row_child_area');
        $columnId = $this->idFromFixture(ElementColumn::class, 'column1');
        $columnChildAreaId = $this->idFromFixture(ElementalArea::class, 'column_child_area');

        $section->delete();

        $this->assertNull(ElementalArea::get()->byID($sectionChildAreaId), 'Section ChildArea should be deleted');
        $this->assertNull(ElementRow::get()->byID($rowId), 'Row should be deleted');
        $this->assertNull(ElementalArea::get()->byID($rowChildAreaId), 'Row ChildArea should be deleted');
        $this->assertNull(ElementColumn::get()->byID($columnId), 'Column should be deleted');
        $this->assertNull(ElementalArea::get()->byID($columnChildAreaId), 'Column ChildArea should be deleted');
    }

    public function testDeletingRowDeletesDescendants(): void
    {
        $sectionId = $this->idFromFixture(ElementSection::class, 'section1');
        $sectionChildAreaId = $this->idFromFixture(ElementalArea::class, 'section_child_area');
        $row = $this->objFromFixture(ElementRow::class, 'row1');
        $rowChildAreaId = $this->idFromFixture(ElementalArea::class, 'row_child_area');
        $columnId = $this->idFromFixture(ElementColumn::class, 'column1');
        $columnChildAreaId = $this->idFromFixture(ElementalArea::class, 'column_child_area');

        $row->delete();

        $this->assertNull(ElementalArea::get()->byID($rowChildAreaId), 'Row ChildArea should be deleted');
        $this->assertNull(ElementColumn::get()->byID($columnId), 'Column should be deleted');
        $this->assertNull(ElementalArea::get()->byID($columnChildAreaId), 'Column ChildArea should be deleted');

        $this->assertNotNull(ElementSection::get()->byID($sectionId), 'Section should survive');
        $this->assertNotNull(ElementalArea::get()->byID($sectionChildAreaId), 'Section ChildArea should survive');
    }

    public function testDeletingColumnDeletesChildArea(): void
    {
        $sectionId = $this->idFromFixture(ElementSection::class, 'section1');
        $sectionChildAreaId = $this->idFromFixture(ElementalArea::class, 'section_child_area');
        $rowId = $this->idFromFixture(ElementRow::class, 'row1');
        $rowChildAreaId = $this->idFromFixture(ElementalArea::class, 'row_child_area');
        $column = $this->objFromFixture(ElementColumn::class, 'column1');
        $columnChildAreaId = $this->idFromFixture(ElementalArea::class, 'column_child_area');

        $column->delete();

        $this->assertNull(ElementalArea::get()->byID($columnChildAreaId), 'Column ChildArea should be deleted');

        $this->assertNotNull(ElementSection::get()->byID($sectionId), 'Section should survive');
        $this->assertNotNull(ElementalArea::get()->byID($sectionChildAreaId), 'Section ChildArea should survive');
        $this->assertNotNull(ElementRow::get()->byID($rowId), 'Row should survive');
        $this->assertNotNull(ElementalArea::get()->byID($rowChildAreaId), 'Row ChildArea should survive');
    }
}
