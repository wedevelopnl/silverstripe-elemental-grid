<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Elements;

use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testValidationFailsWhenNotInsideSection(): void
    {
        // Place Row directly in a page-level area (owned by no container)
        $area = ElementalArea::create();
        $area->write();

        $row = ElementRow::create();
        $row->ParentID = $area->ID;

        $result = $row->validate();

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Section', $result->getMessages()[0]['message']);
    }

    public function testValidationPassesInsideSection(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $result = $row->validate();

        $this->assertTrue($result->isValid());
    }

    public function testValidationPassesWithoutParent(): void
    {
        $row = ElementRow::create();

        $result = $row->validate();

        $this->assertTrue($result->isValid());
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
}
