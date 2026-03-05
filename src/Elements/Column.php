<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Elements;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Contract\ContainerInterface;
use WeDevelop\Grid\Contract\ContainerType;
use WeDevelop\Grid\Contract\GridAdapterInterface;
use WeDevelop\Grid\Model\ContainerElementTrait;
use WeDevelop\Grid\Model\GridElement;

/**
 * Leaf container in the Section > Row > Column hierarchy.
 * Holds responsive grid settings and non-container content elements.
 *
 * @method HasManyList<GridElement> Elements()
 */
class Column extends GridElement implements ContainerInterface
{
    use ContainerElementTrait;

    private static string $table_name = 'Column';

    private static string $singular_name = 'Column';

    private static string $plural_name = 'Columns';

    private static string $icon = 'font-icon-block-content';

    private static string $class_description = 'Responsive grid column that holds content blocks';

    /** @var array<string, string> */
    private static array $dependencies = [
        'gridAdapter' => '%$' . GridAdapterInterface::class,
    ];

    public GridAdapterInterface $gridAdapter;

    /** @var array<string, string> */
    private static array $summary_fields = [
        'Title' => 'Title',
        'getChildCountSummary' => 'Contents',
        'getGridWidthSummary' => 'Width',
    ];

    /** @var array<string, string> */
    private static array $has_many = [
        'Elements' => GridElement::class . '.Parent',
    ];

    /** @var list<string> */
    private static array $owns = [
        'Elements',
    ];

    /** @var list<string> */
    private static array $cascade_deletes = [
        'Elements',
    ];

    /** @var list<string> */
    private static array $cascade_duplicates = [
        'Elements',
    ];

    /** @var array<string, string> */
    private static array $db = [
        'GridSettings' => 'Text',
    ];

    /**
     * Default grid settings: full-width (12/12) across all breakpoints.
     * Override via YAML to change project defaults.
     *
     * @var array<string, array{width: int, offset: int, visible: bool}>
     */
    private static array $default_grid_settings = [
        'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
        'sm' => ['width' => 12, 'offset' => 0, 'visible' => true],
        'md' => ['width' => 12, 'offset' => 0, 'visible' => true],
        'lg' => ['width' => 12, 'offset' => 0, 'visible' => true],
        'xl' => ['width' => 12, 'offset' => 0, 'visible' => true],
    ];

    /** @return HasManyList<GridElement> */
    #[\Override]
    public function getChildren(): HasManyList
    {
        return $this->Elements();
    }

    #[\Override]
    public function getChildTypeName(): string
    {
        return 'element';
    }

    #[\Override]
    public function getContainerType(): ContainerType
    {
        return ContainerType::Column;
    }

    public function getType(): string
    {
        return 'Column';
    }

    /** Render through the holder template. */
    public function forTemplate(): string
    {
        /** @var DBHTMLText $result */
        $result = $this->renderWith('WeDevelop/Grid/Layout/ColumnHolder');

        return (string) $result;
    }

    /** Inner content rendered by `$Element` in the holder template. */
    public function Element(): DBHTMLText
    {
        return $this->renderWith('WeDevelop/Grid/Elements/Column');
    }

    /** Short class name for CSS class generation in templates. */
    public function getSimpleClassName(): string
    {
        return ClassInfo::shortName(static::class);
    }

    /** Returns the first viewport's width as a fraction, e.g. '6/12'. */
    public function getGridWidthSummary(): string
    {
        $settings = $this->getGridSettingsData();
        $defaults = static::config()->get('default_grid_settings');

        $firstKey = array_key_first($settings);
        $defaultKey = array_key_first($defaults);

        if ($firstKey === null || $defaultKey === null) {
            return '';
        }

        return sprintf('%d/%d', $settings[$firstKey]['width'], $defaults[$defaultKey]['width']);
    }

    /**
     * Decode the JSON grid settings into an associative array.
     * Falls back to `default_grid_settings` config when no stored value exists.
     *
     * @return array<string, array{width: int, offset: int, visible: bool}>
     */
    public function getGridSettingsData(): array
    {
        $raw = $this->getField('GridSettings');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                /** @var array<string, array{width: int, offset: int, visible: bool}> $decoded */
                return $decoded;
            }
        }

        return static::config()->get('default_grid_settings');
    }

    /**
     * Encode an associative array of grid settings into JSON for storage.
     *
     * @param array<string, array{width: int, offset: int, visible: bool}> $settings
     */
    public function setGridSettingsData(array $settings): static
    {
        $this->setField('GridSettings', json_encode($settings));

        return $this;
    }

    /** CSS classes for the grid column wrapper. */
    public function getColumnClasses(): string
    {
        $parts = [];
        $settings = $this->getGridSettingsData();

        foreach ($settings as $viewport => $config) {
            if (!$config['visible']) {
                $parts = [
                    ...$parts,
                    ...$this->gridAdapter->getVisibilityClasses($viewport),
                ];
                continue;
            }

            $parts[] = $this->gridAdapter->getWidthClass($viewport, $config['width']);

            if ($config['offset'] > 0) {
                $parts[] = $this->gridAdapter->getOffsetClass($viewport, $config['offset']);
            }
        }

        $classes = implode(' ', $parts);

        $this->extend('updateColumnClasses', $classes);

        return $classes;
    }

    #[\Override]
    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        // Apply default grid settings to new records
        if (!$this->isInDB() && !$this->getField('GridSettings')) {
            $this->setGridSettingsData(static::config()->get('default_grid_settings'));
        }
    }
}
