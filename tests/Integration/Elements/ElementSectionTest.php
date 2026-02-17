<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Elements;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

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

    public function testValidationFailsInsideContainer(): void
    {
        // Place Section inside another Section's ChildArea
        $outerSection = ElementSection::create();
        $outerSection->write();

        $innerSection = ElementSection::create();
        $innerSection->ParentID = $outerSection->getChildArea()->ID;

        $result = $innerSection->validate();

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('container', $result->getMessages()[0]['message']);
    }

    public function testValidationPassesWithoutParent(): void
    {
        $section = ElementSection::create();

        $result = $section->validate();

        $this->assertTrue($result->isValid());
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

    public function testValidationPassesInsidePage(): void
    {
        $page = TestPage::create();
        $page->write();

        $section = ElementSection::create();
        $section->ParentID = $page->ElementalAreaID;

        $result = $section->validate();

        $this->assertTrue($result->isValid());
    }
}
