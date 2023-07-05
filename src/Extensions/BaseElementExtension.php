<?php

namespace WeDevelop\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use WeDevelop\ElementalGrid\CSSFramework\BulmaCSSFramework;
use WeDevelop\ElementalGrid\CSSFramework\BootstrapCSSFramework;
use WeDevelop\ElementalGrid\CSSFramework\CSSFrameworkInterface;
use WeDevelop\ElementalGrid\Models\ElementRow;
use WeDevelop\ElementalGrid\ElementalConfig;

/***
 * Class BaseElementExtension
 * @package WeDevelop\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 * @property string $VisibilityXS
 */
class BaseElementExtension extends DataExtension
{
    private const GRID_COLUMNS_COUNT = 12;

    private static bool $inline_editable = false;

    private CSSFrameworkInterface $cssFramework;

    public function setOwner($owner)
    {
        parent::setOwner($owner);

        match (ElementalConfig::getCSSFrameworkName()) {
            'bulma' => $this->cssFramework = new BulmaCSSFramework($this->owner),
            default => $this->cssFramework = new BootstrapCSSFramework($this->owner),
        };
    }

    public function populateDefaults(): void
    {
        $defaultSizeField = 'Size' . ElementalConfig::getDefaultViewport();
        $this->owner->$defaultSizeField = self::GRID_COLUMNS_COUNT;
    }

    private static array $titleOptions = [
        '' => 'Default',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
    ];

    private static array $db = [
        'SizeXS' => 'Int(2)',
        'SizeSM' => 'Int(2)',
        'SizeMD' => 'Int(2)',
        'SizeLG' => 'Int(2)',
        'SizeXL' => 'Int(2)',
        'OffsetXS' => 'Int(2)',
        'OffsetSM' => 'Int(2)',
        'OffsetMD' => 'Int(2)',
        'OffsetLG' => 'Int(2)',
        'OffsetXL' => 'Int(2)',
        'VisibilityXS' => 'Varchar(10)',
        'VisibilitySM' => 'Varchar(10)',
        'VisibilityMD' => 'Varchar(10)',
        'VisibilityLG' => 'Varchar(10)',
        'VisibilityXL' => 'Varchar(10)',
        'TitleClass' => 'Varchar(255)',
        'TitleTag' => 'Varchar(3)',
    ];

    private static array $defaults = [
        'ShowTitle' => true,
    ];

    public function getTitleHTMLTags()
    {
        return self::$titleOptions;
    }

    public function getTitleClasses(): array
    {
        $classes = (array)self::$titleOptions;

        $this->owner->extend('updateTitleClasses', $classes);

        return $classes;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'Title',
            'TitleClass',
            'TitleTag',
        ]);

        $tab = $fields->findOrMakeTab('Root.Main');

        $tab->unshift(
            FieldGroup::create(
                [
                    TextField::create('Title', _t(__CLASS__ . '.TITLE', 'Title text'))
                        ->addExtraClass('flexbox-area-grow'),
                    DropdownField::create('TitleTag', _t(__CLASS__ . '.TITLE_TAG', 'Title tag'), $this->getTitleHTMLTags()),
                    DropdownField::create('TitleClass', _t(__CLASS__ . '.DISPLAY_AS', 'Display as'), $this->getTitleClasses()),
                    CheckboxField::create('ShowTitle', _t(__CLASS__ . '.DISPLAYED', 'Displayed'))
                        ->addExtraClass('align-self-end'),
                ]
            )
                ->setName('TitleSettings')
                ->setTitle('Title')
                ->addExtraClass('d-lg-flex')
        );

        if (!ElementalConfig::getEnableCustomTitleClasses()) {
            $fields->removeByName('TitleClass');
        }

        $fields->addFieldsToTab(
            'Root.Column',
            [
                HeaderField::create('HeadingXS', _t(__CLASS__ . '.VIEWPORT_XS', 'Extra small (e.g. mobile)')),
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

                HeaderField::create('HeadingSM', _t(__CLASS__ . '.VIEWPORT_SM', 'Small (e.g. portrait tablet)')),
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

                HeaderField::create('HeadingMD', _t(__CLASS__ . '.VIEWPORT_MD', 'Medium (e.g. landscape tablet)')),
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

                HeaderField::create('HeadingLG', _t(__CLASS__ . '.VIEWPORT_LG', 'Large (e.g. normal desktop)')),
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

                HeaderField::create('HeadingXL', _t(__CLASS__ . '.VIEWPORT_XL', 'Extra large (e.g. full HD monitor)')),
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

    public function updateBlockSchema(&$blockSchema)
    {
        $defaultViewportSize = 'Size' . ElementalConfig::getDefaultViewport();
        $defaultViewportOffset = 'Offset' . ElementalConfig::getDefaultViewport();
        $defaultViewportVisibility = 'Visibility' . ElementalConfig::getDefaultViewport();

        $blockSchema['grid'] = [
            'isRow' => $this->owner->ClassName === ElementRow::class,
            'gridColumns' => $this->getGridColumnsCount(),
            'column' => [
                'defaultViewport' => ElementalConfig::getDefaultViewport(),
                'size' => $this->owner->$defaultViewportSize ?? self::GRID_COLUMNS_COUNT,
                'offset' => $this->owner->$defaultViewportOffset,
                'visibility' => $this->owner->$defaultViewportVisibility,
            ],
        ];
    }

    private function getColumnSizeOptions($defaultValue = null): array
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

    public function getTitleTag(): string
    {
        if (is_null($this->owner->getField('TitleTag'))) {
            return $this->owner->TitleSize ?? ElementalConfig::getDefaultTitleTag();
        }

        return $this->owner->getField('TitleTag');
    }

    public function getTitleSizeClass(): string
    {
        $class = $this->owner->getCSSFramework()->getTitleSizeClass();

        $this->owner->extend('updateTitleSizeClass', $class);

        return $class;
    }

    public function getAnchorTitle(): string
    {
        return $this->owner->singular_name() . '_' . $this->owner->getTitle() . '_' . $this->owner->ID;
    }
}
