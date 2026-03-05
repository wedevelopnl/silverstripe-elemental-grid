<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Grid\Contract\ContainerInterface;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Value\ContainerType;

/**
 * Top-level container in the Section > Row > Column hierarchy.
 * Lives under a page (via polymorphic Parent), never inside another container.
 * On draft-stage write, auto-scaffolds a child Row (which cascades
 * to create a Column) when no children exist.
 *
 * @method HasManyList<Row> Rows()
 */
class Section extends GridElement implements ContainerInterface
{
    use ContainerElementTrait;

    private static string $table_name = 'Section';

    private static string $singular_name = 'Section';

    private static string $plural_name = 'Sections';

    private static string $icon = 'font-icon-block-layout';

    private static string $class_description = 'Top-level layout container that holds rows';

    private static bool $fluid_container = false;

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
        'Rows' => Row::class . '.Parent',
    ];

    /** @var list<string> */
    private static array $owns = [
        'Rows',
    ];

    /** @var list<string> */
    private static array $cascade_deletes = [
        'Rows',
    ];

    /** @var list<string> */
    private static array $cascade_duplicates = [
        'Rows',
    ];

    private static string $default_row_title = '';

    private static bool $auto_scaffold = true;

    public function getType(): string
    {
        return 'Section';
    }

    /** @return HasManyList<Row> */
    #[\Override] // @phpstan-ignore method.childReturnType, method.childReturnType (covariant narrowing: Row extends GridElement)
    public function getChildren(): HasManyList
    {
        return $this->Rows();
    }

    #[\Override]
    public function getChildTypeName(): string
    {
        return 'row';
    }

    #[\Override]
    public function getContainerType(): ContainerType
    {
        return ContainerType::Section;
    }

    /** Render through the holder template. */
    public function forTemplate(): string
    {
        /** @var DBHTMLText $result */
        $result = $this->renderWith('WeDevelop/Grid/Layout/SectionHolder');

        return (string) $result;
    }

    /** Inner content rendered by `$Element` in the holder template. */
    public function Element(): DBHTMLText
    {
        return $this->renderWith('WeDevelop/Grid/Model/Section');
    }

    /** Short class name for CSS class generation in templates. */
    public function getSimpleClassName(): string
    {
        return ClassInfo::shortName(static::class);
    }

    /** CSS classes for the grid container wrapper. */
    public function getContainerClasses(): string
    {
        /** @var bool $fluid */
        $fluid = static::config()->get('fluid_container');
        $classes = $this->gridAdapter->getContainerClass($fluid);

        $this->extend('updateContainerClasses', $classes);

        return $classes;
    }

    #[\Override]
    protected function onAfterWrite(): void
    {
        parent::onAfterWrite();

        if (!static::config()->get('auto_scaffold')) {
            return;
        }

        // Only scaffold on draft stage to avoid duplicates during publish
        if (Versioned::get_stage() !== Versioned::DRAFT) {
            return;
        }

        if ($this->Rows()->count() > 0) {
            return;
        }

        $row = Row::create();
        $row->Title = static::config()->get('default_row_title');
        $row->ParentID = $this->ID;
        $row->ParentClass = static::class;
        $row->write();
    }
}
