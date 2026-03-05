<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Elements;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\ContainerInterface;
use WeDevelop\Grid\Elements\Column;
use WeDevelop\Grid\Elements\Row;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Model\GridElement;

#[CoversClass(Column::class)]
final class ColumnTest extends ContainerContractTestCase
{
    protected function createContainer(): ContainerInterface
    {
        $column = Column::create();
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

        $column = Column::create();
        $column->ParentID = $page->ID;
        $column->ParentClass = \Page::class;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed at page level.');
        $column->write();
    }

    public function testWriteBlockedInsideSection(): void
    {
        $section = Section::create();
        $section->write();

        $column = Column::create();
        $column->ParentID = $section->ID;
        $column->ParentClass = Section::class;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed inside Section.');
        $column->write();
    }

    public function testWriteBlockedInsideColumn(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = $row->getChildren()->first();
        $this->assertInstanceOf(Column::class, $column);

        $innerColumn = Column::create();
        $innerColumn->ParentID = $column->ID;
        $innerColumn->ParentClass = Column::class;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Column cannot be placed inside Column.');
        $innerColumn->write();
    }

    public function testWriteSucceedsInsideRow(): void
    {
        $section = Section::create();
        $section->write();

        $row = $section->getChildren()->first();
        $this->assertInstanceOf(Row::class, $row);

        $column = Column::create();
        $column->ParentID = $row->ID;
        $column->ParentClass = Row::class;
        $column->write();

        $this->assertGreaterThan(0, $column->ID);
    }

    public function testDoesNotScaffoldChildren(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $this->assertFalse($column->hasChildren());
    }

    public function testIconConfig(): void
    {
        $this->assertSame('font-icon-block-content', Column::config()->get('icon'));
    }

    public function testPluralNameConfig(): void
    {
        $this->assertSame('Columns', Column::config()->get('plural_name'));
    }

    public function testClassDescriptionConfig(): void
    {
        $this->assertSame(
            'Responsive grid column that holds content blocks',
            Column::config()->get('class_description'),
        );
    }

    public function testGetTypeReturnsColumn(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $this->assertSame('Column', $column->getType());
    }

    public function testGetSummaryReturnsZeroElementsForEmptyColumn(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $this->assertSame('0 elements', $column->getSummary());
    }

    public function testGetGridWidthSummaryWithDefaultSettings(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $this->assertSame('12/12', $column->getGridWidthSummary());
    }

    public function testGetGridWidthSummaryWithCustomSettings(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $settings = $column->getGridSettingsData();
        $settings['xs']['width'] = 6;
        $column->setGridSettingsData($settings);
        $column->write();

        $this->assertSame('6/12', $column->getGridWidthSummary());
    }

    public function testGetChildCountSummaryIsPubliclyCallable(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $this->assertSame('0 elements', $column->getChildCountSummary());
    }

    public function testGetChildCountSummarySingularWithOneChild(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $leaf = GridElement::create();
        $leaf->Title = 'Test Leaf';
        $leaf->ParentID = $column->ID;
        $leaf->ParentClass = Column::class;
        $leaf->write();

        $this->assertSame('1 element', $column->getChildCountSummary());
    }

    public function testGetGridWidthSummaryReturnsEmptyForEmptyGridSettings(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $column->setField('GridSettings', '[]');
        $column->write();

        $this->assertSame('', $column->getGridWidthSummary());
    }

    public function testSummaryFieldsIncludesContentsAndWidthColumns(): void
    {
        $fields = Column::config()->get('summary_fields');

        $this->assertArrayHasKey('getChildCountSummary', $fields);
        $this->assertSame('Contents', $fields['getChildCountSummary']);
        $this->assertArrayHasKey('getGridWidthSummary', $fields);
        $this->assertSame('Width', $fields['getGridWidthSummary']);
    }

    public function testDefaultGridSettingsAppliedFromConfig(): void
    {
        $column = $this->createContainer();
        /** @var Column $column */

        $expected = Column::config()->get('default_grid_settings');
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
        /** @var Column $column */
        $column->setGridSettingsData($settings);
        $column->write();

        // Re-fetch from DB to verify persistence
        $reloaded = Column::get()->byID($column->ID);
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

        Column::config()->set('default_grid_settings', $custom);

        $column = Column::create();
        $column->write();

        $this->assertSame($custom, $column->getGridSettingsData());
    }

    public function testOnBeforeWritePersistsGridSettingsFieldForNewRecord(): void
    {
        $column = Column::create();
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

        $column = Column::create();
        $column->setGridSettingsData($custom);
        $column->write();

        $reloaded = Column::get()->byID($column->ID);
        $this->assertSame($custom, $reloaded->getGridSettingsData());
    }
}
