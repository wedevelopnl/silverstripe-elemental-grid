<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Elements;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\ElementContainerInterface;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Tests\Integration\Fixture\OnAfterWriteSpy;

#[CoversClass(Row::class)]
final class RowTest extends ContainerContractTestCase
{
    protected function createContainer(): ElementContainerInterface
    {
        $row = Row::create();
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

        $row = Row::create();
        $row->ParentID = $page->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Page.');
        $row->write();
    }

    public function testWriteBlockedInsideRow(): void
    {
        $section = Section::create();
        $section->write();

        $parentRow = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $parentRow);

        $childRow = Row::create();
        $childRow->ParentID = $parentRow->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Row.');
        $childRow->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        $childRow = Row::create();
        $childRow->ParentID = $column->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Column.');
        $childRow->write();
    }

    public function testWriteSucceedsInsideSection(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $row->write();

        $this->assertGreaterThan(0, $row->ID);
    }

    public function testScaffoldsColumnOnWrite(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $this->assertTrue($row->hasChildren());

        $children = $row->getChildren();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(Column::class, $children->first());
    }

    public function testPublishDoesNotDuplicateScaffoldedColumn(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $row->publishRecursive();

        // Re-read draft version
        $row = Row::get()->byID($row->ID);
        $this->assertCount(1, $row->getChildren());
    }

    public function testDefaultColumnTitleConfigIsRespected(): void
    {
        Row::config()->set('default_column_title', 'Custom Column');

        $row = Row::create();
        $row->write();

        $column = $row->getChildren()->first();
        $this->assertSame('Custom Column', $column->Title);
    }

    public function testSubsequentWriteDoesNotDuplicateScaffoldedChildren(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $this->assertCount(1, $row->getChildren());

        $row->Title = 'Updated';
        $row->write();

        $row = Row::get()->byID($row->ID);
        $this->assertCount(1, $row->getChildren());
    }

    public function testDoesNotScaffoldOnNonDraftStage(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::LIVE);

            $row = Row::create();
            $row->write();

            $this->assertFalse($row->hasChildren());
        });
    }

    public function testIconConfig(): void
    {
        $this->assertSame('font-icon-columns', Row::config()->get('icon'));
    }

    public function testPluralNameConfig(): void
    {
        $this->assertSame('Rows', Row::config()->get('plural_name'));
    }

    public function testClassDescriptionConfig(): void
    {
        $this->assertSame(
            'Horizontal container that holds columns within a section',
            Row::config()->get('class_description'),
        );
    }

    public function testGetTypeReturnsRow(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $this->assertSame('Row', $row->getType());
    }

    public function testGetSummaryReturnsSingularColumnCount(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        // Scaffolding creates 1 column
        $this->assertSame('1 column', $row->getSummary());
    }

    public function testGetSummaryReturnsPluralColumnCount(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $extraColumn = Column::create();
        $extraColumn->ParentID = $row->ID;
        $extraColumn->write();

        $this->assertSame('2 columns', $row->getSummary());
    }

    public function testGetChildCountSummaryIsPubliclyCallable(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        $this->assertSame('1 column', $row->getChildCountSummary());
    }

    public function testSummaryFieldsIncludesContentsColumn(): void
    {
        $fields = Row::config()->get('summary_fields');

        $this->assertArrayHasKey('getChildCountSummary', $fields);
        $this->assertSame('Contents', $fields['getChildCountSummary']);
    }

    /**
     * Regression: moving a row into its own column's area would create a
     * circular reference. The fixed hierarchy type rules prevent this —
     * rows are never allowed inside columns.
     */
    public function testCircularReferenceBlockedByTypeRulesWhenMovedIntoOwnColumn(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        // Attempt to reparent the row into its own column's child area
        $row->ParentID = $column->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Row cannot be placed inside Column.');
        $row->write();
    }

    public function testOnAfterWriteInvokesParentHook(): void
    {
        OnAfterWriteSpy::$called = false;
        Row::add_extension(OnAfterWriteSpy::class);

        try {
            $row = Row::create();
            $row->write();

            $this->assertTrue(OnAfterWriteSpy::$called);
        } finally {
            Row::remove_extension(OnAfterWriteSpy::class);
        }
    }

    public function testRewriteOnNonDraftStageDoesNotScaffold(): void
    {
        $row = $this->createContainer();
        /** @var Row $row */

        // Remove the scaffolded child so container has no children
        $child = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $child);
        $child->delete();

        $this->assertCount(0, $row->getChildren());

        // Re-write on LIVE stage — should NOT scaffold a new child
        Versioned::withVersionedMode(function () use ($row): void {
            Versioned::set_stage(Versioned::LIVE);
            $row->Title = 'Rewritten on LIVE';
            $row->write();
        });

        $this->assertCount(0, $row->getChildren());
    }
}
