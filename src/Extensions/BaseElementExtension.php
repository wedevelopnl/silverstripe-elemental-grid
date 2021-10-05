<?php

namespace TheWebmen\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
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

/***
 * Class BaseElementExtension
 * @package TheWebmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class BaseElementExtension extends DataExtension
{
    private const GRID_COLUMNS_COUNT = 12;

    /**
     * @var bool
     */
    private static $inline_editable = false;

    /**
     * @var CSSFrameworkInterface
     */
    private $cssFramework;

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

    public function populateDefaults()
    {
        $defaultSizeField = 'Size' . $this->getDefaultViewport();
        $this->owner->$defaultSizeField = self::GRID_COLUMNS_COUNT;
    }

    /**
     * @var array|string[]
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
     * @var array|string[]
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
     * @var array|string[]
     */
    private static $defaults = [
        'ShowTitle' => true,
    ];

    public function getTitleHTMLTags(): array
    {
        return self::$titleOptions;
    }

    public function getTitleClasses(): array
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
        $fields->removeByName('Title');
        $fields->removeByName('TitleClass');
        $fields->removeByName('TitleTag');

        /***
         * Used insert before with an empty string as argument here, to force the
         * TitleSettings group to always appear first in the fieldorder
         */
        $fields->insertBefore(
            FieldGroup::create(
                    [
                        TextField::create('Title', 'Title text')
                            ->addExtraClass('flexbox-area-grow'),
                        DropdownField::create('TitleTag', 'HTML Tag', $this->getTitleHTMLTags()),
                        DropdownField::create('TitleClass', 'Display as', $this->getTitleClasses()),
                        CheckboxField::create('ShowTitle', 'Displayed')
                            ->addExtraClass('align-self-end'),
                    ]
                )
                    ->setName('TitleSettings')
                    ->setTitle('Title')
                    ->addExtraClass('d-lg-flex'),
            ''
        );

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
        $defaultViewportSize = 'Size' . $this->getDefaultViewport();
        $defaultViewportOffset = 'Offset' . $this->getDefaultViewport();
        $defaultViewportVisibility = 'Visibility' . $this->getDefaultViewport();

        $blockSchema['grid'] = [
            'isRow' => $this->owner->ClassName === ElementRow::class,
            'gridColumns' => $this->getGridColumnsCount(),
            'column' => [
                'defaultViewport' => $this->getDefaultViewport(),
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
            $columns[$i] = sprintf('%s %u/%u', _t(__CLASS__ . '.COLUMN', 'Column'), $i, $this->getGridColumnsCount());
        }

        return $columns;
    }

    /**
     * @return array
     */
    private function getColumnVisibilityOptions()
    {
        return [
            'visible' => _t(__CLASS__ . '.VISIBLE', 'Visible'),
            'hidden' => _t(__CLASS__ . '.HIDDEN', 'Hidden'),
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
    private function getDefaultViewport()
    {
        return Config::forClass('TheWebmen\ElementalGrid')->get('default_viewport');
    }

    /**
     * @return string
     */
    private function getCSSFrameworkName()
    {
        return Config::forClass('TheWebmen\ElementalGrid')->get('css_framework');
    }

    /**
     * @return string
     */
    public function getTitleSizeClass()
    {
        return $this->owner->getCSSFramework()->getTitleSizeClass();
    }
}
