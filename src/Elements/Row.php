<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Elements;

use SilverStripe\ORM\HasManyList;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Model\ContainerElement;

/**
 * Mid-level container in the Section > Row > Column hierarchy.
 * Lives inside a Section only. On draft-stage write, auto-scaffolds
 * a child Column when no children exist.
 *
 * @method HasManyList<Column> Columns()
 */
class Row extends ContainerElement
{
    private static string $table_name = 'Row';

    private static string $singular_name = 'Row';

    private static string $plural_name = 'Rows';

    private static string $icon = 'font-icon-columns';

    private static string $class_description = 'Horizontal container that holds columns within a section';

    /** @var array<string, string> */
    private static array $dependencies = [
        'gridAdapter' => '%$' . GridAdapterInterface::class,
    ];

    public GridAdapterInterface $gridAdapter;

    /** @var array<string, string> */
    private static array $summary_fields = [
        'Title' => 'Title',
        'getChildCountSummary' => 'Contents',
    ];

    /** @var array<string, string> */
    private static array $has_many = [
        'Columns' => Column::class . '.Parent',
    ];

    /** @var list<string> */
    private static array $owns = [
        'Columns',
    ];

    /** @var list<string> */
    private static array $cascade_deletes = [
        'Columns',
    ];

    /** @var list<string> */
    private static array $cascade_duplicates = [
        'Columns',
    ];

    private static string $default_column_title = '';

    public function getType(): string
    {
        return 'Row';
    }

    /** @return HasManyList<Column> */
    #[\Override] // @phpstan-ignore method.childReturnType, method.childReturnType (covariant narrowing: Column extends GridElement)
    public function getChildren(): HasManyList
    {
        return $this->Columns();
    }

    #[\Override]
    public function getChildTypeName(): string
    {
        return 'column';
    }

    #[\Override]
    public function getContainerType(): ContainerType
    {
        return ContainerType::Row;
    }

    /** CSS classes for the grid row wrapper. */
    public function getRowClasses(): string
    {
        $classes = $this->gridAdapter->getRowClasses();

        $this->extend('updateRowClasses', $classes);

        return $classes;
    }

    #[\Override]
    protected function onAfterWrite(): void
    {
        parent::onAfterWrite();

        // Only scaffold on draft stage to avoid duplicates during publish
        if (Versioned::get_stage() !== Versioned::DRAFT) {
            return;
        }

        if ($this->Columns()->count() > 0) {
            return;
        }

        $column = Column::create();
        $column->Title = static::config()->get('default_column_title');
        $column->ParentID = $this->ID;
        $column->ParentClass = static::class;
        $column->write();
    }
}
