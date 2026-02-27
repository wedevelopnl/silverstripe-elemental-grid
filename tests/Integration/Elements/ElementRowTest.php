<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Elements;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;

#[CoversClass(ElementRow::class)]
final class ElementRowTest extends ElementContainerContractTestCase
{
    protected function createContainer(): ElementContainerInterface
    {
        $row = ElementRow::create();
        $row->write();

        return $row;
    }

    public function testGetContainerTypeReturnsRow(): void
    {
        $row = $this->createContainer();

        $this->assertSame(ContainerType::Row, $row->getContainerType());
    }

    public function testWriteBlockedOnPage(): void
    {
        $page = \Page::create();
        $page->Title = 'Test Page';
        $page->write();

        $row = ElementRow::create();
        $row->ParentID = $page->ElementalArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Page.');
        $row->write();
    }

    public function testWriteBlockedInsideRow(): void
    {
        $section = ElementSection::create();
        $section->write();

        $parentRow = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $parentRow);

        $childRow = ElementRow::create();
        $childRow->ParentID = $parentRow->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Row.');
        $childRow->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        $childRow = ElementRow::create();
        $childRow->ParentID = $column->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Column.');
        $childRow->write();
    }

    public function testWriteSucceedsInsideSection(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $row->write();

        $this->assertGreaterThan(0, $row->ID);
    }

    public function testScaffoldsColumnOnWrite(): void
    {
        $row = $this->createContainer();
        /** @var ElementRow $row */

        $this->assertTrue($row->hasChildren());

        $children = $row->getChildArea()->Elements();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementColumn::class, $children->first());
    }

    public function testPublishDoesNotDuplicateScaffoldedColumn(): void
    {
        $row = $this->createContainer();
        /** @var ElementRow $row */

        $row->publishRecursive();

        // Re-read draft version
        $row = ElementRow::get()->byID($row->ID);
        $this->assertCount(1, $row->getChildArea()->Elements());
    }

    public function testDefaultColumnTitleConfigIsRespected(): void
    {
        ElementRow::config()->set('default_column_title', 'Custom Column');

        $row = ElementRow::create();
        $row->write();

        $column = $row->getChildArea()->Elements()->first();
        $this->assertSame('Custom Column', $column->Title);
    }

    public function testSubsequentWriteDoesNotDuplicateScaffoldedChildren(): void
    {
        $row = $this->createContainer();
        /** @var ElementRow $row */

        $this->assertCount(1, $row->getChildArea()->Elements());

        $row->Title = 'Updated';
        $row->write();

        $row = ElementRow::get()->byID($row->ID);
        $this->assertCount(1, $row->getChildArea()->Elements());
    }

    public function testDoesNotScaffoldOnNonDraftStage(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::LIVE);

            $row = ElementRow::create();
            $row->write();

            $this->assertFalse($row->hasChildren());
        });
    }

    /**
     * Regression: moving a row into its own column's area would create a
     * circular reference. The fixed hierarchy type rules prevent this —
     * rows are never allowed inside columns.
     */
    public function testCircularReferenceBlockedByTypeRulesWhenMovedIntoOwnColumn(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        // Attempt to reparent the row into its own column's child area
        $row->ParentID = $column->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Column.');
        $row->write();
    }
}
