<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Forms;

use DNADesign\Elemental\Extensions\ElementalAreasExtension;
use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Forms\GridEditorField;
use WeDevelop\ElementalGrid\Tests\Integration\Fixture\TestPage;

#[CoversClass(GridEditorField::class)]
final class GridEditorFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

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

    protected function setUp(): void
    {
        parent::setUp();
        Versioned::set_stage(Versioned::DRAFT);
    }

    public function testFieldNameMatchesConstructorArgument(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertSame('ElementalArea', $field->getName());
    }

    public function testGetAreaReturnsProvidedArea(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertSame($area, $field->getArea());
    }

    public function testHasGridEditorContainerCssClass(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertStringContainsString('grid-editor__container', $field->extraClass());
    }

    public function testHasNoChangeTrackCssClass(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertStringContainsString('no-change-track', $field->extraClass());
    }

    public function testSchemaDataContainsGridAreaId(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);
        $schemaData = $field->getSchemaDataDefaults();

        $this->assertIsInt($schemaData['grid-area-id']);
        $this->assertSame((int) $area->ID, $schemaData['grid-area-id']);
        $this->assertGreaterThan(0, $schemaData['grid-area-id']);
    }

    public function testSchemaDataContainsGridPageIdWhenPageExists(): void
    {
        $page = TestPage::create();
        $page->Title = 'Test Page';
        $page->write();

        /** @var ElementalArea $area */
        $area = $page->ElementalArea();

        $field = GridEditorField::create('ElementalArea', $area);
        $schemaData = $field->getSchemaDataDefaults();

        $this->assertIsInt($schemaData['grid-page-id']);
        $this->assertSame((int) $page->ID, $schemaData['grid-page-id']);
    }

    public function testSchemaDataHasNullPageIdWhenNoPage(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);
        $schemaData = $field->getSchemaDataDefaults();

        $this->assertNull($schemaData['grid-page-id']);
    }

    public function testAcceptsBlockTypesParameter(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $types = ['DNADesign\Elemental\Models\ElementContent'];
        $field = GridEditorField::create('ElementalArea', $area, $types);

        $this->assertSame($types, $field->getBlockTypes());
    }

    public function testBlockTypesDefaultsToEmptyArray(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertSame([], $field->getBlockTypes());
    }

    public function testPerformReadonlyTransformationReturnsLiteralField(): void
    {
        $area = ElementalArea::create();
        $area->write();

        $field = GridEditorField::create('ElementalArea', $area);

        $this->assertInstanceOf(LiteralField::class, $field->performReadonlyTransformation());
    }
}
