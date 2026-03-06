<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Dev;

use Page;
use SilverStripe\Forms\FieldList;
use WeDevelop\Grid\Forms\GridEditorField;

/**
 * Dev-only page type with two grid zones (main + sidebar) on the Main tab.
 *
 * Used by E2E tests to verify cross-zone drag isolation and backend
 * rejection of cross-zone section reorder attempts.
 *
 * Not marked TestOnly because TestOnly classes are unavailable at E2E runtime.
 * Gated to dev environment via _config/dev.yml.
 */
class MultiZonePage extends Page
{
    private static string $table_name = 'GridMultiZonePage';

    private static string $singular_name = 'Multi-Zone Page';

    private static string $description = 'Dev-only page with main + sidebar grid zones';

    /** Prevent this page type from appearing in the CMS "Add new page" dropdown. */
    private static string $hide_ancestor = self::class;

    #[\Override]
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Content');
        $fields->removeByName('GridEditor');

        $fields->addFieldToTab(
            'Root.Main',
            GridEditorField::create('GridEditorMain', (int) $this->ID, 'main'),
        );
        $fields->addFieldToTab(
            'Root.Main',
            GridEditorField::create('GridEditorSidebar', (int) $this->ID, 'sidebar'),
        );

        return $fields;
    }
}
