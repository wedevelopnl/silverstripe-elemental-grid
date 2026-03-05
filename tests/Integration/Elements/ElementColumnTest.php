<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Elements;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\ElementContainerInterface;
use WeDevelop\Grid\Elements\ElementColumn;
use WeDevelop\Grid\Elements\ElementRow;
use WeDevelop\Grid\Elements\ElementSection;

#[CoversClass(ElementColumn::class)]
final class ElementColumnTest extends ElementContainerContractTestCase
{
    protected function createContainer(): ElementContainerInterface
    {
        $column = ElementColumn::create();
        $column->write();

        return $column;
    }

    public function testGetContainerTypeReturnsColumn(): void
    {
        $column = $this->createContainer();

        $this->assertSame(ContainerType::Column, $column->getContainerType());
    }

    public function testWriteBlockedOnPage(): void
    {
        $page = \Page::create();
        $page->Title = 'Test Page';
        $page->write();

        $column = ElementColumn::create();
        $column->ParentID = $page->ElementalArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed inside Page.');
        $column->write();
    }

    public function testWriteBlockedInsideSection(): void
    {
        $section = ElementSection::create();
        $section->write();

        $column = ElementColumn::create();
        $column->ParentID = $section->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed inside Section.');
        $column->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = $row->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementColumn::class, $column);

        $innerColumn = ElementColumn::create();
        $innerColumn->ParentID = $column->getChildArea()->ID;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed inside Column.');
        $innerColumn->write();
    }

    public function testWriteSucceedsInsideRow(): void
    {
        $section = ElementSection::create();
        $section->write();

        $row = $section->getChildArea()->Elements()->first();
        $this->assertInstanceOf(ElementRow::class, $row);

        $column = ElementColumn::create();
        $column->ParentID = $row->getChildArea()->ID;
        $column->write();

        $this->assertGreaterThan(0, $column->ID);
    }

    public function testDoesNotScaffoldChildren(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $this->assertFalse($column->hasChildren());
    }

    public function testIconConfig(): void
    {
        $this->assertSame('font-icon-block-content', ElementColumn::config()->get('icon'));
    }

    public function testPluralNameConfig(): void
    {
        $this->assertSame('Columns', ElementColumn::config()->get('plural_name'));
    }

    public function testClassDescriptionConfig(): void
    {
        $this->assertSame(
            'Responsive grid column that holds content blocks',
            ElementColumn::config()->get('class_description'),
        );
    }

    public function testGetTypeReturnsColumn(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $this->assertSame('Column', $column->getType());
    }

    public function testGetSummaryReturnsZeroElementsForEmptyColumn(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $this->assertSame('0 elements', $column->getSummary());
    }

    public function testGetGridWidthSummaryWithDefaultSettings(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $this->assertSame('12/12', $column->getGridWidthSummary());
    }

    public function testGetGridWidthSummaryWithCustomSettings(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $settings = $column->getGridSettingsData();
        $settings['xs']['width'] = 6;
        $column->setGridSettingsData($settings);
        $column->write();

        $this->assertSame('6/12', $column->getGridWidthSummary());
    }

    public function testGetChildCountSummaryIsPubliclyCallable(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $this->assertSame('0 elements', $column->getChildCountSummary());
    }

    public function testGetChildCountSummarySingularWithOneChild(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $leaf = \DNADesign\Elemental\Models\BaseElement::create();
        $leaf->Title = 'Test Leaf';
        $leaf->ParentID = $column->getChildArea()->ID;
        $leaf->write();

        $this->assertSame('1 element', $column->getChildCountSummary());
    }

    public function testGetGridWidthSummaryReturnsEmptyForEmptyGridSettings(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $column->setField('GridSettings', '[]');
        $column->write();

        $this->assertSame('', $column->getGridWidthSummary());
    }

    public function testSummaryFieldsIncludesContentsAndWidthColumns(): void
    {
        $fields = ElementColumn::config()->get('summary_fields');

        $this->assertArrayHasKey('getChildCountSummary', $fields);
        $this->assertSame('Contents', $fields['getChildCountSummary']);
        $this->assertArrayHasKey('getGridWidthSummary', $fields);
        $this->assertSame('Width', $fields['getGridWidthSummary']);
    }

    public function testDefaultGridSettingsAppliedFromConfig(): void
    {
        $column = $this->createContainer();
        /** @var ElementColumn $column */

        $expected = ElementColumn::config()->get('default_grid_settings');
        $this->assertSame($expected, $column->getGridSettingsData());
    }

    public function testGridSettingsRoundTrip(): void
    {
        $settings = [
            'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 6, 'offset' => 3, 'visible' => true],
            'md' => ['width' => 4, 'offset' => 0, 'visible' => false],
            'lg' => ['width' => 8, 'offset' => 2, 'visible' => true],
            'xl' => ['width' => 10, 'offset' => 1, 'visible' => true],
        ];

        $column = $this->createContainer();
        /** @var ElementColumn $column */
        $column->setGridSettingsData($settings);
        $column->write();

        // Re-fetch from DB to verify persistence
        $reloaded = ElementColumn::get()->byID($column->ID);
        $this->assertSame($settings, $reloaded->getGridSettingsData());
    }

    public function testDefaultGridSettingsConfigIsRespected(): void
    {
        $custom = [
            'xs' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ];

        ElementColumn::config()->set('default_grid_settings', $custom);

        $column = ElementColumn::create();
        $column->write();

        $this->assertSame($custom, $column->getGridSettingsData());
    }

    public function testOnBeforeWritePersistsGridSettingsFieldForNewRecord(): void
    {
        $column = ElementColumn::create();
        $this->assertNull($column->getField('GridSettings'));

        $column->write();

        $raw = $column->getField('GridSettings');
        $this->assertIsString($raw);
        $this->assertJson($raw);
    }

    public function testPresetGridSettingsNotOverwrittenOnFirstWrite(): void
    {
        $custom = [
            'xs' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'sm' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'md' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'lg' => ['width' => 6, 'offset' => 0, 'visible' => true],
            'xl' => ['width' => 6, 'offset' => 0, 'visible' => true],
        ];

        $column = ElementColumn::create();
        $column->setGridSettingsData($custom);
        $column->write();

        $reloaded = ElementColumn::get()->byID($column->ID);
        $this->assertSame($custom, $reloaded->getGridSettingsData());
    }
}
