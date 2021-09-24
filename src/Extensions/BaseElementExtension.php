<?php

namespace Webmen\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataExtension;
use Webmen\ElementalGrid\CSSFramework\BulmaCSSFramework;
use Webmen\ElementalGrid\CSSFramework\BootstrapCSSFramework;
use Webmen\ElementalGrid\CSSFramework\CSSFrameworkInterface;
use Webmen\ElementalGrid\Models\ElementRow;

/***
 * Class BaseElementExtension
 * @package Webmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class BaseElementExtension extends DataExtension
{
    private const GRID_COLUMNS_COUNT = 12;

    private static bool $inline_editable = false;

    private CSSFrameworkInterface $cssFramework;

    public function setOwner($owner)
    {
        parent::setOwner($owner);

        switch ($this->getCSSFrameworkName()) {
            case 'bulma':
                $this->cssFramework = new BulmaCSSFramework($this->owner);
                break;
            default:
                $this->cssFramework = new BootstrapCSSFramework($this->owner);
        }
    }

    public function populateDefaults(): void
    {
        $defaultSizeField = 'Size' . $this->getDefaultViewport();
        $this->owner->$defaultSizeField = Config::forClass('Webmen\ElementalGrid')->get('default_column_size');
    }

    private static array $db = [
        'SizeXS' => 'Int',
        'SizeSM' => 'Int',
        'SizeMD' => 'Int',
        'SizeLG' => 'Int',
        'SizeXL' => 'Int',
        'OffsetXS' => 'Int',
        'OffsetSM' => 'Int',
        'OffsetMD' => 'Int',
        'OffsetLG' => 'Int',
        'OffsetXL' => 'Int',
        'VisibilityXS' => 'Varchar(10)',
        'VisibilitySM' => 'Varchar(10)',
        'VisibilityMD' => 'Varchar(10)',
        'VisibilityLG' => 'Varchar(10)',
        'VisibilityXL' => 'Varchar(10)',
    ];

    /**
     * Update the default CMS fields with our custom fields
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->findOrMakeTab('Root.Column', _t(__CLASS__ . '.COLUMN', 'Column'));

        $fields->addFieldsToTab(
            'Root.Column',
            [
                HeaderField::create('HeadingXS', _t(__CLASS__ . '.XS', 'Extra small (e.g. mobile)')),
                DropdownField::create(
                    'SizeXS',
                    _t(__CLASS__ . '.SIZE_XS', 'Size XS'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.DEFAULT', 'Default'))
                ),
                DropdownField::create(
                    'OffsetXS',
                    _t(__CLASS__ . '.OFFSET_XS', 'Offset XS'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.NONE', 'None'))
                ),
                DropdownField::create(
                    'VisibilityXS',
                    _t(__CLASS__ . '.VISIBILITY_XS', 'Visibility XS'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingSM', _t(__CLASS__ . '.SM', 'Small (e.g. portrait tablet)')),
                DropdownField::create(
                    'SizeSM',
                    _t(__CLASS__ . '.SIZE_SM', 'Size SM'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.DEFAULT', 'Default'))
                ),
                DropdownField::create(
                    'OffsetSM',
                    _t(__CLASS__ . '.OFFSET_SM', 'Offset SM'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.NONE', 'None'))
                ),
                DropdownField::create(
                    'VisibilitySM',
                    _t(__CLASS__ . '.VISIBILITY_SM', 'Visibility SM'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingMD', _t(__CLASS__ . '.MD', 'Medium (e.g. landscape tablet)')),
                DropdownField::create(
                    'SizeMD',
                    _t(__CLASS__ . '.SIZE_MD', 'Size MD'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.DEFAULT', 'Default'))
                )
                    ->addExtraClass('sizing')
                    ->setAttribute('data-column-size', 'md'),
                DropdownField::create(
                    'OffsetMD',
                    _t(__CLASS__ . '.OFFSET_MD', 'Offset MD'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.NONE', 'None'))
                ),
                DropdownField::create(
                    'VisibilityMD',
                    _t(__CLASS__ . '.VISIBILITY_MD', 'Visibility MD'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingLG', _t(__CLASS__ . '.LG', 'Large (e.g. normal desktop)')),
                DropdownField::create(
                    'SizeLG',
                    _t(__CLASS__ . '.SIZE_LG', 'Size LG'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.DEFAULT', 'Default'))
                ),
                DropdownField::create(
                    'OffsetLG',
                    _t(__CLASS__ . '.OFFSET_LG', 'Offset LG'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.NONE', 'None'))
                ),
                DropdownField::create(
                    'VisibilityLG',
                    _t(__CLASS__ . '.VISIBILITY_LG', 'Visibility LG'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingXL', _t(__CLASS__ . '.XL', 'Extra large (e.g. full HD monitor)')),
                DropdownField::create(
                    'SizeXL',
                    _t(__CLASS__ . '.SIZE_XL', 'Size XL'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.DEFAULT', 'Default'))
                ),
                DropdownField::create(
                    'OffsetXL',
                    _t(__CLASS__ . '.OFFSET_XL', 'Offset XL'),
                    self::getColumnSizeOptions(_t(__CLASS__ . '.NONE', 'None'))
                ),
                DropdownField::create(
                    'VisibilityXL',
                    _t(__CLASS__ . '.VISIBILITY_XL', 'Visibility XL'),
                    self::getColumnVisibilityOptions()
                ),
            ]
        );
    }

    public function getColumnClasses(): string
    {
        return implode(' ', [$this->cssFramework->getColumnClasses(), $this->owner->ExtraClass]);
    }

    public function getCSSFramework(): CSSFrameworkInterface
    {
        return $this->cssFramework;
    }

    /***
     * Add the extra data to the blockSchema object, to be taken up by
     * GraphQL and used in the react component in the CMS admin
     */
    public function updateBlockSchema(&$blockSchema): void
    {
        $defaultViewportSize = 'Size' . $this->getDefaultViewport();
        $defaultViewportOffset = 'Offset' . $this->getDefaultViewport();
        $defaultViewportVisibility = 'Visibility' . $this->getDefaultViewport();

        $blockSchema['grid'] = [
            'isRow' => $this->owner->ClassName === ElementRow::class,
            'gridColumns' => $this->getGridColumnsCount(),
            'column' => [
                'defaultViewport' => $this->getDefaultViewport(),
                'size' => $this->owner->$defaultViewportSize ?? Config::forClass('Webmen\ElementalGrid')->get(
                    'default_column_size'
                ),
                'offset' => $this->owner->$defaultViewportOffset,
                'visibility' => $this->owner->$defaultViewportVisibility,
            ],
        ];
    }

    /***
     * Returns an array of all possibile column widths
     */
    private function getColumnSizeOptions(?string $defaultValue = null): array
    {
        $columns = [];

        if ($defaultValue) {
            $columns[0] = $defaultValue;
        }

        for ($i = 1; $i < $this->getGridColumnsCount() + 1; $i++) {
            $columns[$i] = sprintf('%s %u/%u', _t(__CLASS__ . '.COLUMN', 'Column'), $i, $this->getGridColumnsCount());
        }

        return $columns;
    }

    private function getColumnVisibilityOptions(): array
    {
        return [
            'visible' => _t(__CLASS__ . '.VISIBLE', 'Visible'),
            'hidden' => _t(__CLASS__ . '.HIDDEN', 'Hidden'),
        ];
    }

    private function getGridColumnsCount(): int
    {
        return self::GRID_COLUMNS_COUNT;
    }

    private function getDefaultViewport(): string
    {
        return Config::forClass('Webmen\ElementalGrid')->get('default_viewport');
    }

    private function getCSSFrameworkName()
    {
        return Config::forClass('Webmen\ElementalGrid')->get('css_framework');
    }
}
