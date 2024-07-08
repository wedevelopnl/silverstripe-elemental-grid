<?php

namespace WeDevelop\ElementalGrid\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use WeDevelop\ElementalGrid\CSSFramework\CSSFrameworkInterface;
use WeDevelop\ElementalGrid\CSSFramework\TailwindCSSFramework;
use WeDevelop\ElementalGrid\Models\ElementRow;
use WeDevelop\ElementalGrid\ElementalConfig;

class BaseElementExtension extends DataExtension
{
    private static bool $inline_editable = false;

    private CSSFrameworkInterface $cssFramework;

    public function setOwner($owner)
    {
        parent::setOwner($owner);

        $frameworks = ClassInfo::implementorsOf(CSSFrameworkInterface::class);
        foreach ($frameworks as $framework) {
            if ($framework::$framework_key === ElementalConfig::getCSSFrameworkName()) {
                $this->cssFramework = new $framework($this->owner);
            }
        }
        if(!$this->cssFramework) {
            $this->cssFramework = new TailwindCSSFramework($this->owner);
        }

        $this->owner->extend('updateCSSFramework', $this->cssFramework);
    }

    public function populateDefaults(): void
    {
        $defaultSizeField = 'Size' . ElementalConfig::getDefaultViewport();
        $this->owner->$defaultSizeField = ElementalConfig::getGridColumnCount();
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
        'TitleClass' => 'Varchar(255)',
        'TitleTag' => 'Varchar(3)',
    ];

    private static array $defaults = [
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

    public function updateCMSFields(FieldList $fields): void
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
            'Root.Responsive',
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
            'gridColumns' => ElementalConfig::getGridColumnCount(),
            'column' => [
                'defaultViewport' => ElementalConfig::getDefaultViewport(),
                'size' => $this->owner->$defaultViewportSize ?? ElementalConfig::getGridColumnCount(),
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

        for ($i = 1; $i < ElementalConfig::getGridColumnCount() + 1; $i++) {
            $columns[$i] = sprintf('%s %u/%u', _t(__CLASS__ . '.COLUMN', 'Column'), $i, ElementalConfig::getGridColumnCount());
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

    public function getTitleTag(): string
    {
        if (is_null($this->owner->getField('TitleTag'))) {
            return $this->owner->TitleSize ?? ElementalConfig::getDefaultTitleTag();
        }

        return $this->owner->getField('TitleTag');
    }

    public function getTitleSizeClass(): ?string
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
