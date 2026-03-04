<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Elements;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;

/**
 * Mid-level container in the Section > Row > Column hierarchy.
 * Lives inside a Section's ChildArea only. On draft-stage write,
 * auto-scaffolds a child Column when the ChildArea is empty.
 */
class ElementRow extends BaseElement implements ElementContainerInterface
{
    private static string $table_name = 'ElementRow';

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

    /** @var array<string, class-string> */
    private static array $has_one = [
        'ChildArea' => ElementalArea::class,
    ];

    /** @var list<string> */
    private static array $owns = [
        'ChildArea',
    ];

    /** @var list<string> */
    private static array $cascade_deletes = [
        'ChildArea',
    ];

    /** @var list<string> */
    private static array $cascade_duplicates = [
        'ChildArea',
    ];

    private static string $default_column_title = '';

    public function getType(): string
    {
        return 'Row';
    }

    public function getChildCountSummary(): string
    {
        $count = $this->getChildArea()->Elements()->count();

        return sprintf('%d %s', $count, $count === 1 ? 'column' : 'columns');
    }

    public function getSummary(): string
    {
        return $this->getChildCountSummary();
    }

    #[\Override]
    public function getChildArea(): ElementalArea
    {
        return $this->ChildArea();
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return $this->getChildArea()->Elements()->exists();
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

        $childArea = $this->getChildArea();
        if (!$childArea->exists() || $childArea->Elements()->count() > 0) {
            return;
        }

        $column = ElementColumn::create();
        $column->Title = static::config()->get('default_column_title');
        $column->ParentID = $childArea->ID;
        $column->write();
    }
}
