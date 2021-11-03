<?php

namespace TheWebmen\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use TheWebmen\ElementalGrid\CSSFramework\BulmaCSSFramework;
use TheWebmen\ElementalGrid\CSSFramework\BootstrapCSSFramework;
use TheWebmen\ElementalGrid\CSSFramework\CSSFrameworkInterface;
use TheWebmen\ElementalGrid\Models\ElementRow;
use TheWebmen\ElementalGrid\ElementalConfig;

/***
 * Class BaseElementExtension
 * @package TheWebmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class BaseElementExtension extends DataExtension
{
    /***
     * @var int
     */
    private const GRID_COLUMNS_COUNT = 12;

    /**
     * @var bool
     */
    private static $inline_editable = false;

    /**
     * @var CSSFrameworkInterface
     */
    private $cssFramework;

    /***
     * @param object $owner
     */
    public function setOwner($owner)
    {
        parent::setOwner($owner);

        switch (ElementalConfig::getCSSFrameworkName()) {
            case 'bulma':
                $this->cssFramework = new BulmaCSSFramework($this->owner);
                break;
            default:
                $this->cssFramework = new BootstrapCSSFramework($this->owner);
        }
    }

    /***
     * @return void
     */
    public function populateDefaults()
    {
        $defaultSizeField = 'Size' . ElementalConfig::getDefaultViewport();
        $this->owner->$defaultSizeField = self::GRID_COLUMNS_COUNT;
    }

    /**
     * @var array
     */
    private static $titleOptions = [
        'div' => 'Default',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
    ];

    /**
     * @var array
     */
    private static $db = [
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
        'TitleClass' => 'Varchar(100)',
        'TitleTag' => 'Varchar(100)',
    ];

    /***
     * @var array
     */
    private static $defaults = [
        'ShowTitle' => true,
    ];

    /***
     * @return array
     */
    public function getTitleHTMLTags()
    {
        return self::$titleOptions;
    }

    /***
     * @return array
     */
    public function getTitleClasses()
    {
        $classes = self::$titleOptions;

        $this->owner->extend('updateTitleClasses', $classes);

        return $classes;
    }

    /**
     * Update the default CMS fields with our custom fields
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['Title', 'TitleClass', 'TitleTag']);

        /***
         * Used insert before with an empty string as argument here, to force the
         * TitleSettings group to always appear first in the fieldorder
         */
        $fields->insertBefore(
            FieldGroup::create(
                [
                    TextField::create('Title', _t('Element.Title.Title', 'Title text'))
                        ->addExtraClass('flexbox-area-grow'),
                    DropdownField::create('TitleTag', _t('Element.Title.Tag', 'Title tag'), $this->getTitleHTMLTags()),
                    DropdownField::create('TitleClass', _t('Element.Title.DisplayAs', 'Display as'), $this->getTitleClasses()),
                    CheckboxField::create('ShowTitle', _t('Element.Title.Displayed', 'Displayed'))
                        ->addExtraClass('align-self-end'),
                ]
            )
                ->setName('TitleSettings')
                ->setTitle('Title')
                ->addExtraClass('d-lg-flex'),
            ''
        );

        if (!ElementalConfig::getEnableCustomTitleClasses()) {
            $fields->removeByName('TitleClass');
        }

        $fields->findOrMakeTab('Root.Column', _t('Column.Label', 'Column'));

        $fields->addFieldsToTab(
            'Root.Column',
            [
                HeaderField::create('HeadingXS', _t('Column.Viewport.XS', 'Extra small (e.g. mobile)')),
                DropdownField::create(
                    'SizeXS',
                    _t('Column.Size.XS', 'Size XS'),
                    self::getColumnSizeOptions(_t('Column.Default', 'Default'))
                ),
                DropdownField::create(
                    'OffsetXS',
                    _t('Column.Offset.XS', 'Offset XS'),
                    self::getColumnSizeOptions(_t('Column.None', 'None'))
                ),
                DropdownField::create(
                    'VisibilityXS',
                    _t('Column.Visibility.XS', 'Visibility XS'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingSM', _t('Column.Viewport.SM', 'Small (e.g. portrait tablet)')),
                DropdownField::create(
                    'SizeSM',
                    _t('Column.Size.SM', 'Size SM'),
                    self::getColumnSizeOptions(_t('Column.Default', 'Default'))
                ),
                DropdownField::create(
                    'OffsetSM',
                    _t('Column.Offset.SM', 'Offset SM'),
                    self::getColumnSizeOptions(_t('Column.None', 'None'))
                ),
                DropdownField::create(
                    'VisibilitySM',
                    _t('Column.Visibility.SM', 'Visibility SM'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingMD', _t('Column.Viewport.MD', 'Medium (e.g. landscape tablet)')),
                DropdownField::create(
                    'SizeMD',
                    _t('Column.Size.MD', 'Size MD'),
                    self::getColumnSizeOptions(_t('Column.Default', 'Default'))
                )
                    ->addExtraClass('sizing')
                    ->setAttribute('data-column-size', 'md'),
                DropdownField::create(
                    'OffsetMD',
                    _t('Column.Offset.MD', 'Offset MD'),
                    self::getColumnSizeOptions(_t('Column.None', 'None'))
                ),
                DropdownField::create(
                    'VisibilityMD',
                    _t('Column.Visibility.MD', 'Visibility MD'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingLG', _t('Column.Viewport.LG', 'Large (e.g. normal desktop)')),
                DropdownField::create(
                    'SizeLG',
                    _t('Column.Size.LG', 'Size LG'),
                    self::getColumnSizeOptions(_t('Column.Default', 'Default'))
                ),
                DropdownField::create(
                    'OffsetLG',
                    _t('Column.Offset.LG', 'Offset LG'),
                    self::getColumnSizeOptions(_t('Column.None', 'None'))
                ),
                DropdownField::create(
                    'VisibilityLG',
                    _t('Column.Visibility.LG', 'Visibility LG'),
                    self::getColumnVisibilityOptions()
                ),

                HeaderField::create('HeadingXL', _t('Column.Viewport.XL', 'Extra large (e.g. full HD monitor)')),
                DropdownField::create(
                    'SizeXL',
                    _t('Column.Size.XL', 'Size XL'),
                    self::getColumnSizeOptions(_t('Column.Default', 'Default'))
                ),
                DropdownField::create(
                    'OffsetXL',
                    _t('Column.Offset.XL', 'Offset XL'),
                    self::getColumnSizeOptions(_t('Column.None', 'None'))
                ),
                DropdownField::create(
                    'VisibilityXL',
                    _t('Column.Visibility.XL', 'Visibility XL'),
                    self::getColumnVisibilityOptions()
                ),
            ]
        );
    }

    /**
     * @return string
     */
    public function getColumnClasses()
    {
        return implode(' ', [$this->cssFramework->getColumnClasses(), $this->owner->ExtraClass]);
    }

    /**
     * @return CSSFrameworkInterface
     */
    public function getCSSFramework()
    {
        return $this->cssFramework;
    }

    /***
     * Add the extra data to the blockSchema object, to be taken up by
     * GraphQL and used in the react component in the CMS admin
     */
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

    /***
     * Returns an array of all possibile column widths
     *
     * @param string|null $defaultValue
     * @return array
     */
    private function getColumnSizeOptions($defaultValue = null)
    {
        $columns = [];

        if ($defaultValue) {
            $columns[0] = $defaultValue;
        }

        for ($i = 1; $i < $this->getGridColumnsCount() + 1; $i++) {
            $columns[$i] = sprintf('%s %u/%u', _t('Column.Label', 'Column'), $i, $this->getGridColumnsCount());
        }

        return $columns;
    }

    /**
     * @return array
     */
    private function getColumnVisibilityOptions()
    {
        return [
            'visible' => _t('Column.Visibility.Visible', 'Visible'),
            'hidden' => _t('Column.Visibility.Hidden', 'Hidden'),
        ];
    }

    /**
     * @return int
     */
    private function getGridColumnsCount()
    {
        return self::GRID_COLUMNS_COUNT;
    }

    /**
     * @return string
     */
    public function getTitleTag()
    {
        if(is_null($this->owner->getField('TitleTag'))) {
            return $this->owner->TitleSize ?? 'h2';
        }

        return $this->owner->getField('TitleTag');
    }

    /**
     * @return string
     */
    public function getTitleSizeClass()
    {
        return $this->owner->getCSSFramework()->getTitleSizeClass();
    }
}
