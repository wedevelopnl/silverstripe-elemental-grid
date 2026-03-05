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
use WeDevelop\Grid\Extensions\GridPageExtension;
use WeDevelop\Grid\Tests\Integration\Fixture\OnAfterWriteSpy;
use WeDevelop\Grid\Tests\Integration\Fixture\TestPage;

#[CoversClass(Section::class)]
final class SectionTest extends ContainerContractTestCase
{
    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            GridPageExtension::class,
        ],
    ];

    protected function createContainer(): ElementContainerInterface
    {
        $section = Section::create();
        $section->write();

        return $section;
    }

    public function testGetContainerTypeReturnsSection(): void
    {
        $section = $this->createContainer();

        $this->assertSame(ContainerType::Section, $section->getContainerType());
    }

    public function testWriteSucceedsOnPage(): void
    {
        $page = TestPage::create();
        $page->Title = 'Test Page';
        $page->write();

        $section = Section::create();
        $section->ParentID = $page->ID;
        $section->write();

        $this->assertGreaterThan(0, $section->ID);
    }

    public function testWriteBlockedInsideSection(): void
    {
        $outerSection = Section::create();
        $outerSection->write();

        $innerSection = Section::create();
        $innerSection->ParentID = $outerSection->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Section.');
        $innerSection->write();
    }

    public function testWriteBlockedInsideRow(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $innerSection = Section::create();
        $innerSection->ParentID = $row->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Row.');
        $innerSection->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        $innerSection = Section::create();
        $innerSection->ParentID = $column->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Column.');
        $innerSection->write();
    }

    public function testScaffoldsRowOnWrite(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $this->assertTrue($section->hasChildren());

        $children = $section->getChildren();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(Row::class, $children->first());
    }

    public function testScaffoldingCascadesToColumn(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);
        $this->assertTrue($row->hasChildren());

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testPublishDoesNotDuplicateScaffoldedChildren(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $section->publishRecursive();

        // Re-read draft version
        $section = Section::get()->byID($section->ID);
        $this->assertCount(1, $section->getChildren());

        $row = $section->getChildren()->first();
        $this->assertCount(1, $row->getChildren());
    }

    public function testDefaultRowTitleConfigIsRespected(): void
    {
        Section::config()->set('default_row_title', 'Custom Row');

        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertSame('Custom Row', $row->Title);
    }

    public function testSubsequentWriteDoesNotDuplicateScaffoldedChildren(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $this->assertCount(1, $section->getChildren());

        $section->Title = 'Updated';
        $section->write();

        $section = Section::get()->byID($section->ID);
        $this->assertCount(1, $section->getChildren());
    }

    public function testDoesNotScaffoldOnNonDraftStage(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::LIVE);

            $section = Section::create();
            $section->write();

            $this->assertFalse($section->hasChildren());
        });
    }

    public function testOnAfterWriteInvokesParentHook(): void
    {
        OnAfterWriteSpy::$called = false;
        Section::add_extension(OnAfterWriteSpy::class);

        try {
            $section = Section::create();
            $section->write();

            $this->assertTrue(OnAfterWriteSpy::$called);
        } finally {
            Section::remove_extension(OnAfterWriteSpy::class);
        }
    }

    public function testRewriteOnNonDraftStageDoesNotScaffold(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        // Remove the scaffolded child so container has no children
        $child = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $child);
        $child->delete();

        $this->assertCount(0, $section->getChildren());

        // Re-write on LIVE stage — should NOT scaffold a new child
        Versioned::withVersionedMode(function () use ($section): void {
            Versioned::set_stage(Versioned::LIVE);
            $section->Title = 'Rewritten on LIVE';
            $section->write();
        });

        $this->assertCount(0, $section->getChildren());
    }

    public function testValidationPassesInsidePage(): void
    {
        $page = TestPage::create();
        $page->write();

        $section = Section::create();
        $section->ParentID = $page->ID;

        $result = $section->validate();

        $this->assertTrue($result->isValid());
    }

    public function testIconConfig(): void
    {
        $this->assertSame('font-icon-block-layout', Section::config()->get('icon'));
    }

    public function testPluralNameConfig(): void
    {
        $this->assertSame('Sections', Section::config()->get('plural_name'));
    }

    public function testClassDescriptionConfig(): void
    {
        $this->assertSame(
            'Top-level layout container that holds rows',
            Section::config()->get('class_description'),
        );
    }

    public function testGetTypeReturnsSection(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $this->assertSame('Section', $section->getType());
    }

    public function testGetSummaryReturnsSingularRowCount(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        // Scaffolding creates 1 row
        $this->assertSame('1 row', $section->getSummary());
    }

    public function testGetSummaryReturnsPluralRowCount(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $extraRow = Row::create();
        $extraRow->ParentID = $section->ID;
        $extraRow->write();

        $this->assertSame('2 rows', $section->getSummary());
    }

    public function testGetChildCountSummaryIsPubliclyCallable(): void
    {
        $section = $this->createContainer();
        /** @var Section $section */

        $this->assertSame('1 row', $section->getChildCountSummary());
    }

    public function testSummaryFieldsIncludesContentsColumn(): void
    {
        $fields = Section::config()->get('summary_fields');

        $this->assertArrayHasKey('getChildCountSummary', $fields);
        $this->assertSame('Contents', $fields['getChildCountSummary']);
    }

    /**
     * Regression: moving a section into its own row's area would create a
     * circular reference. The fixed hierarchy type rules prevent this —
     * sections are never allowed inside rows.
     */
    public function testCircularReferenceBlockedByTypeRulesWhenMovedIntoOwnRow(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        // Attempt to reparent the section into its own row's child area
        $section->ParentID = $row->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Row.');
        $section->write();
    }

    /**
     * Regression: moving a section into its own column's area would create a
     * circular reference. The fixed hierarchy type rules prevent this —
     * sections are never allowed inside columns.
     */
    public function testCircularReferenceBlockedByTypeRulesWhenMovedIntoOwnColumn(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        // Attempt to reparent the section into its own column's child area
        $section->ParentID = $column->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Column.');
        $section->write();
    }
}
