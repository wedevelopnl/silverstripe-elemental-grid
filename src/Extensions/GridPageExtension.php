<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Forms\GridEditorField;

/**
 * Adds grid editing capability to SiteTree pages.
 *
 * Provides the has_many relationship for top-level Sections and
 * injects the GridEditorField into the CMS editing form.
 *
 * @extends Extension<SiteTree>
 * @method HasManyList<Section> Sections()
 */
class GridPageExtension extends Extension
{
    /** @var array<string, class-string> */
    private static array $has_many = [
        'Sections' => Section::class . '.Parent',
    ];

    /** @var list<string> */
    private static array $owns = [
        'Sections',
    ];

    /** @var list<string> */
    private static array $cascade_deletes = [
        'Sections',
    ];

    /** @var list<string> */
    private static array $cascade_duplicates = [
        'Sections',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        /** @var SiteTree $owner */
        $owner = $this->owner;

        $fields->addFieldToTab(
            'Root.Main',
            GridEditorField::create('GridEditor', (int) $owner->ID),
        );
    }
}
