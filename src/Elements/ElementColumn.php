<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Elements;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use WeDevelop\ElementalGrid\Contract\ContainerType;
use WeDevelop\ElementalGrid\Contract\ElementContainerInterface;

/**
 * Leaf container in the Section > Row > Column hierarchy.
 * Holds responsive grid settings and simple (non-container) content elements.
 */
class ElementColumn extends BaseElement implements ElementContainerInterface
{
    private static string $table_name = 'ElementColumn';

    private static string $singular_name = 'Column';

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
        return ContainerType::Column;
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
