<?php

namespace TheWebmen\ElementalGrid\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataExtension;
use TheWebmen\ElementalGrid\Models\ElementRow;

class BaseElementExtension extends DataExtension
{
    private static int $num_columns = 12;

    private static int $default_col_size = 6;

    private static array $db = [
        'SizeXS' => 'Int',
        'SizeSM' => 'Int',
        'SizeMD' => 'Int',
        'SizeLG' => 'Int',
        'OffsetXS' => 'Int',
        'OffsetSM' => 'Int',
        'OffsetMD' => 'Int',
        'OffsetLG' => 'Int',
        'VisibilityXS' => 'Varchar',
        'VisibilitySM' => 'Varchar',
        'VisibilityMD' => 'Varchar',
        'VisibilityLG' => 'Varchar',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->findOrMakeTab('Root.Column', _t(__CLASS__ . '.COLUMN', 'Column'));
        $fields->addFieldsToTab('Root.Column', [
            HeaderField::create('HeadingXS', _t(__CLASS__ . '.XS', 'XS')),
            DropdownField::create('SizeXS', _t(__CLASS__ . '.SIZE_XS', 'Size XS'), self::getColSizeOptions(true)),
            DropdownField::create('OffsetXS', _t(__CLASS__ . '.OFFSET_XS', 'Offset XS'), self::getColSizeOptions(false, true)),
            DropdownField::create('VisibilityXS', _t(__CLASS__ . '.VISIBILITY_XS', 'Visibility XS'), self::getColVisibilityOptions()),
            HeaderField::create('HeadingSM', _t(__CLASS__ . '.SM', 'SM')),
            DropdownField::create('SizeSM', _t(__CLASS__ . '.SIZE_SM', 'Size SM'), self::getColSizeOptions(true)),
            DropdownField::create('OffsetSM', _t(__CLASS__ . '.OFFSET_SM', 'Offset SM'), self::getColSizeOptions(false, true)),
            DropdownField::create('VisibilitySM', _t(__CLASS__ . '.VISIBILITY_SM', 'Visibility SM'), self::getColVisibilityOptions()),
            HeaderField::create('HeadingMD', _t(__CLASS__ . '.MD', 'MD')),
            DropdownField::create('SizeMD', _t(__CLASS__ . '.SIZE_MD', 'Size MD'), self::getColSizeOptions(true))
                ->addExtraClass('sizing')
                ->setAttribute('data-column-size', 'md'),
            DropdownField::create('OffsetMD', _t(__CLASS__ . '.OFFSET_MD', 'Offset MD'), self::getColSizeOptions(false, true)),
            DropdownField::create('VisibilityMD', _t(__CLASS__ . '.VISIBILITY_MD', 'Visibility MD'), self::getColVisibilityOptions()),
            HeaderField::create('HeadingLG', _t(__CLASS__ . '.LG', 'LG')),
            DropdownField::create('SizeLG', _t(__CLASS__ . '.SIZE_LG', 'Size LG'), self::getColSizeOptions(true)),
            DropdownField::create('OffsetLG', _t(__CLASS__ . '.OFFSET_LG', 'Offset LG'), self::getColSizeOptions(false, true)),
            DropdownField::create('VisibilityLG', _t(__CLASS__ . '.VISIBILITY_LG', 'Visibility LG'), self::getColVisibilityOptions()),
        ]);
    }

    public static function getColSizeOptions($includeDefault = false, $includeNone = false)
    {
        $config = Config::inst()->get(__CLASS__);
        $numColumns = $config['num_columns'];
        $out = array();
        if ($includeDefault) {
            $out[0] = _t(__CLASS__ . '.DEFAULT', 'Default');
        } else if ($includeNone) {
            $out[0] = _t(__CLASS__ . '.NONE', 'None');
        }
        for ($i = 1; $i < $numColumns + 1; $i++) {
            $out[$i] = _t(__CLASS__ . '.COLUMN', 'Column') . ' ' . $i . '/' . $numColumns;
        }
        return $out;
    }

    public static function getColVisibilityOptions()
    {
        $out = array();
        $out['default'] = _t(__CLASS__ . '.DEFAULT', 'Default');
        $out['visible'] = _t(__CLASS__ . '.VISIBLE', 'Visible');
        $out['hidden'] = _t(__CLASS__ . '.HIDDEN', 'Hidden');
        return $out;
    }

    public function updateBlockSchema(&$blockSchema)
    {
        $blockSchema['grid'] = [
            'isRow' => $this->owner->ClassName === ElementRow::class,
            'md' => [
                'size' => $this->owner->SizeMD ?: $this->owner->config()->get('default_col_size'),
                'offset' => $this->owner->OffsetMD,
                'visibility' => $this->owner->VisibilityMD,
            ],
        ];
    }
}
