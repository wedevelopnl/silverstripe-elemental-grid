<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Elements;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Versioned\Versioned;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;

/**
 * Top-level container in the Section > Row > Column hierarchy.
 * Lives in a page's ElementalArea, never inside another container.
 * On draft-stage write, auto-scaffolds a child Row (which cascades
 * to create a Column) when the ChildArea is empty.
 */
class ElementSection extends BaseElement implements ElementContainerInterface
{
    private static string $table_name = 'ElementSection';

    private static string $singular_name = 'Section';

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

    private static string $default_row_title = '';

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
        return ContainerType::Section;
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

        $row = ElementRow::create();
        $row->Title = static::config()->get('default_row_title');
        $row->ParentID = $childArea->ID;
        $row->write();
    }
}
