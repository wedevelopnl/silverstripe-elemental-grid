<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Elements;

use PHPUnit\Framework\Attributes\CoversClass;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Elements\ElementColumn;
use WeDevelop\ElementalGrid\Elements\ElementRow;
use WeDevelop\ElementalGrid\Elements\ElementSection;

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
