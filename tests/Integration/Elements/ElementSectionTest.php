<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Elements;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\ElementContainerInterface;
use WeDevelop\Grid\Elements\ElementColumn;
use WeDevelop\Grid\Elements\ElementRow;
use WeDevelop\Grid\Elements\ElementSection;
use WeDevelop\Grid\Tests\Integration\Fixture\OnAfterWriteSpy;
use WeDevelop\Grid\Tests\Integration\Fixture\TestPage;

#[CoversClass(ElementSection::class)]
final class ElementSectionTest extends ElementContainerContractTestCase
{
    /** @var list<class-string> */
    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    /** @var array<class-string, list<class-string>> */
    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
        ],
    ];

    protected function createContainer(): ElementContainerInterface
    {
        $section = ElementSection::create();
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

        $section = ElementSection::create();
        $section->ParentID = $page->ElementalAreaID;
        $section->write();

        $this->assertGreaterThan(0, $section->ID);
    }

    public function testWriteBlockedInsideSection(): void
    {
        $outerSection = ElementSection::create();
        $outerSection->write();

        $innerSection = ElementSection::create();
        $innerSection->ParentID = $outerSection->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Section.');
        $innerSection->write();
    }

    public function testWriteBlockedInsideRow(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $innerSection = ElementSection::create();
        $innerSection->ParentID = $row->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Row.');
        $innerSection->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        $innerSection = ElementSection::create();
        $innerSection->ParentID = $column->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Column.');
        $innerSection->write();
    }

    public function testScaffoldsRowOnWrite(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $this->assertTrue($section->hasChildren());

        $children = $section->getChildArea()->Elements();
        $this->assertCount(1, $children);
        $this->assertInstanceOf(ElementRow::class, $children->first());
    }

    public function testScaffoldingCascadesToColumn(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);
        $this->assertTrue($row->hasChildren());

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);
    }

    public function testPublishDoesNotDuplicateScaffoldedChildren(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $section->publishRecursive();

        // Re-read draft version
        $section = ElementSection::get()->byID($section->ID);
        $this->assertCount(1, $section->getChildArea()->Elements());

        $row = $section->getChildArea()->Elements()->first();
        $this->assertCount(1, $row->getChildArea()->Elements());
    }

    public function testDefaultRowTitleConfigIsRespected(): void
    {
        ElementSection::config()->set('default_row_title', 'Custom Row');

        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertSame('Custom Row', $row->Title);
    }

    public function testSubsequentWriteDoesNotDuplicateScaffoldedChildren(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $this->assertCount(1, $section->getChildArea()->Elements());

        $section->Title = 'Updated';
        $section->write();

        $section = ElementSection::get()->byID($section->ID);
        $this->assertCount(1, $section->getChildArea()->Elements());
    }

    public function testDoesNotScaffoldOnNonDraftStage(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::LIVE);

            $section = ElementSection::create();
            $section->write();

            $this->assertFalse($section->hasChildren());
        });
    }

    public function testOnAfterWriteInvokesParentHook(): void
    {
        OnAfterWriteSpy::$called = false;
        ElementSection::add_extension(OnAfterWriteSpy::class);

        try {
            $section = ElementSection::create();
            $section->write();

            $this->assertTrue(OnAfterWriteSpy::$called);
        } finally {
            ElementSection::remove_extension(OnAfterWriteSpy::class);
        }
    }

    public function testRewriteOnNonDraftStageDoesNotScaffold(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        // Remove the scaffolded child so ChildArea is empty
        $child = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $child);
        $child->delete();

        $this->assertCount(0, $section->getChildArea()->Elements());

        // Re-write on LIVE stage — should NOT scaffold a new child
        Versioned::withVersionedMode(function () use ($section): void {
            Versioned::set_stage(Versioned::LIVE);
            $section->Title = 'Rewritten on LIVE';
            $section->write();
        });

        $this->assertCount(0, $section->getChildArea()->Elements());
    }

    public function testValidationPassesInsidePage(): void
    {
        $page = TestPage::create();
        $page->write();

        $section = ElementSection::create();
        $section->ParentID = $page->ElementalAreaID;

        $result = $section->validate();

        $this->assertTrue($result->isValid());
    }

    public function testIconConfig(): void
    {
        $this->assertSame('font-icon-block-layout', ElementSection::config()->get('icon'));
    }

    public function testPluralNameConfig(): void
    {
        $this->assertSame('Sections', ElementSection::config()->get('plural_name'));
    }

    public function testClassDescriptionConfig(): void
    {
        $this->assertSame(
            'Top-level layout container that holds rows',
            ElementSection::config()->get('class_description'),
        );
    }

    public function testGetTypeReturnsSection(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $this->assertSame('Section', $section->getType());
    }

    public function testGetSummaryReturnsSingularRowCount(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        // Scaffolding creates 1 row
        $this->assertSame('1 row', $section->getSummary());
    }

    public function testGetSummaryReturnsPluralRowCount(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $extraRow = ElementRow::create();
        $extraRow->ParentID = $section->getChildArea()->ID;
        $extraRow->write();

        $this->assertSame('2 rows', $section->getSummary());
    }

    public function testGetChildCountSummaryIsPubliclyCallable(): void
    {
        $section = $this->createContainer();
        /** @var ElementSection $section */

        $this->assertSame('1 row', $section->getChildCountSummary());
    }

    public function testSummaryFieldsIncludesContentsColumn(): void
    {
        $fields = ElementSection::config()->get('summary_fields');

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
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        // Attempt to reparent the section into its own row's child area
        $section->ParentID = $row->getChildArea()->ID;

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
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        // Attempt to reparent the section into its own column's child area
        $section->ParentID = $column->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Section cannot be placed inside Column.');
        $section->write();
    }
}
