<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use WeDevelop\ElementalGrid\ElementalConfig;
use WeDevelop\MediaField\Form\MediaField;

/**
 * @method Image MediaImage()
 * @property ElementContent|ElementContentExtension $owner
 */
final class ElementContentExtension extends DataExtension
{
    private static array $db = [
        'ContentColumns' => 'Varchar(64)',
        'ContentVerticalAlign' => 'Varchar(64)',
        'ExtraColumnGap' => 'Int(2)',

        'MediaType' => 'Varchar(5)',
        'MediaCaption' => 'Varchar(255)',
        'MediaRatio' => 'Varchar(10)',
        'MediaPosition' => 'Varchar(64)',

        'MediaVideoFullURL' => 'Varchar(255)',
        'MediaVideoProvider' => 'Varchar(10)',
        'MediaVideoHasOverlay' => 'Boolean(false)',

        'MediaVideoEmbeddedName' => 'Varchar(255)',
        'MediaVideoEmbeddedURL' => 'Varchar(255)',
        'MediaVideoEmbeddedDescription' => 'Text',
        'MediaVideoEmbeddedThumbnail' => 'Varchar(255)',
        'MediaVideoEmbeddedCreated' => 'Varchar(255)',
    ];

    private static array $has_one = [
        'MediaImage' => Image::class,
        'MediaVideoCustomThumbnail' => Image::class,
    ];

    private static array $owns = [
        'MediaImage',
        'MediaVideoCustomThumbnail',
    ];

    private static array $mediaRatios = [
        '' => 'Auto (default)',
        '1x1' => '1x1',
        '4x3' => '4x3',
        '16x9' => '16x9',
    ];

    private static array $contentColumns = [
        '' => 'vertical.png',
        '10' => 'horizontal_2-10.png',
        '9' => 'horizontal_3-9.png',
        '8' => 'horizontal_4-8.png',
        '7' => 'horizontal_5-7.png',
        '6' => 'horizontal_6-6.png',
        '5' => 'horizontal_7-5.png',
        '4' => 'horizontal_8-4.png',
    ];

    private static array $contentVerticalAligments = [
        '' => 'Top',
        'align-items-center' => 'Center (default)',
        'align-items-flex-end' => 'Bottom',
    ];

    private static array $imagePositions = [
        'order-1' => 'Always first (default)',
        'order-2' => 'Always last',
        'order-1 order-md-2' => 'Last on desktop, first on mobile/tablet devices',
    ];

    private static array $columnGaps = [
        '' => 'None (default)',
        '5' => 'Small',
        '7' => 'Normal',
        '9' => 'Medium',
        '11' => 'Large',
    ];

    private static array $defaults = [
        'ContentVerticalAlign' => 'align-items-center',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName([
            'ContentColumns',
            'ExtraColumnGap',
            'ContentVerticalAlign',

            'MediaType',
            'MediaCaption',
            'MediaRatio',
            'MediaPosition',

            'MediaVideoShortURL',
            'MediaVideoFullURL',
            'MediaVideoProvider',
            'MediaVideoCustomThumbnail',
            'MediaVideoHasOverlay',

            'MediaVideoEmbeddedName',
            'MediaVideoEmbeddedURL',
            'MediaVideoEmbeddedDescription',
            'MediaVideoEmbeddedThumbnail',
            'MediaVideoEmbeddedCreated',
        ]);

        $fields->insertBefore('Settings', new Tab('Media'));

        $mediaField = MediaField::create($fields);
        $mediaField->setTitle(_t(__CLASS__ . '.VIDEO_SETTINGS', 'Video settings'));
        $mediaField->getVideoWrapper()->push(
            UploadField::create('MediaVideoCustomThumbnail', _t(__CLASS__ . '.CUSTOM_VIDEO_THUMBNAIL', 'Custom video thumbnail'))
                ->setFolderName('MediaUploads')
                ->setDescription(_t(__CLASS__ . '.OVERWRITES_DEFAULT_THUMBNAIL', 'This overwrites the default thumbnail provided by youtube or vimeo')),
        );

        $fields->addFieldsToTab('Root.Media', [
            HeaderField::create('', _t(__CLASS__ . '.MEDIA_FILE', 'Media file'))->setHeadingLevel(1),
            $mediaField,
            Wrapper::create([
                CheckboxField::create('MediaVideoHasOverlay', _t(__CLASS__ . '.SHOW_OVERLAY', 'Show dark overlay on top of video thumbnail')),
            ])->displayIf('MediaType')->isEqualTo('video')->end(),
            TextField::create('MediaCaption', _t(__CLASS__ . '.CAPTION_TEXT', 'Caption text')),
            DropdownField::create('MediaRatio', _t(__CLASS__ . '.MEDIA_RATIO', 'Media ratio'), self::$mediaRatios)
                ->setEmptyString(_t(__CLASS__ . '.AUTO_DEFAULT', 'Auto (default)'))
                ->setDescription(_t(__CLASS__ . '.DEFAULT_RATIO_DESCRIPTION', 'By default, \'Auto\' will make videos appear as 16x9 ratio, while images will be shown as they are')),
        ]);

        $fields->addFieldsToTab('Root.Media', [
            HeaderField::create('', _t(__CLASS__ . '.MEDIA_POSITIONING', 'Media positioning'))->setHeadingLevel(1),
            OptionsetField::create('ContentColumns', _t(__CLASS__ . '.CONTENT_COLUMN_WIDTH', 'Width of the content column'), self::$contentColumns)
                ->setTemplate('WeDevelop/ElementalGrid/Forms/OptionsetImageField')
                ->addExtraClass('optionset-image-field'),
            DropdownField::create('MediaPosition', _t(__CLASS__ . '.MEDIA_POSITION', 'Media position'), self::$imagePositions),
            Wrapper::create([
                DropdownField::create('ContentVerticalAlign', _t(__CLASS__ . '.VERTICAL_ALIGNMENT', 'Vertical alignment'), self::$contentVerticalAligments),
                DropdownField::create('ExtraColumnGap', _t(__CLASS__ . '.EXTRA_COLUMN_GAP_SIZE', 'Extra column gap size'), self::$columnGaps),
            ])->displayIf('ContentColumns')->isNotEmpty()->end(),
        ]);


        if ($this->owner->MediaType === 'video') {
            $fields->addFieldsToTab('Root.VideoEmbeddedData', [
                ReadonlyField::create('MediaVideoEmbeddedURL', _t(__CLASS__ . '.SHORTENED_URL', 'Shortened URL')),
                ReadonlyField::create('MediaVideoProvider', _t(__CLASS__ . '.VIDEO_PROVIDER', 'Video provider')),
                ReadonlyField::create('MediaVideoEmbeddedName', _t(__CLASS__ . '.EMBEDDED_NAME', 'Embedded name')),
                ReadonlyField::create('MediaVideoEmbeddedDescription', _t(__CLASS__ . '.EMBEDDED_DESCRIPTION', 'Embedded description')),
                ReadonlyField::create('MediaVideoEmbeddedThumbnail', _t(__CLASS__ . '.EMBEDDED_THUMBNAIL_URL', 'Embedded thumbnail URL')),
                FieldGroup::create([
                    LiteralField::create('MediaVideoEmbeddedThumbnailPreview', '<img src="' . $this->owner->MediaVideoEmbeddedThumbnail . '">', _t(__CLASS__ . '.EMBEDDED_THUMBNAIL', 'Embedded thumbnail')),
                ])->setTitle(_t(__CLASS__ . '.VIDEO_THUMBNAIL', 'Video Thumbnail')),
                ReadonlyField::create('MediaVideoEmbeddedCreated', _t(__CLASS__ . '.EMBEDDED_PUBLICATION_DATE', 'Embedded publication date')),
            ]);
        }
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if ($this->owner->MediaType === 'video' && $this->owner->MediaVideoFullURL) {
            $this->owner->MediaVideoFullURL = trim($this->owner->MediaVideoFullURL);
            MediaField::saveEmbed($this->owner);
        }
    }

    public function getMediaRatioClass()
    {
        $mediaRatio = $this->owner->MediaRatio;

        if (!$this->owner->MediaRatio && $this->owner->MediaType === 'video') {
            $mediaRatio = '16x9';
        }

        return $this->owner->getCSSFramework()->getMediaRatioClass($mediaRatio);
    }

    public function ElementClasses(): string
    {
        $elementClasses[] = $this->owner->getCSSFramework()->getRowClasses();

        if ($this->owner->ContentColumns && $this->owner->ContentVerticalAlign) {
            if (ElementalConfig::getCSSFrameworkName() === 'bulma') {
                $elementClasses[] = 'is-' . $this->owner->ContentVerticalAlign;
            } else {
                $elementClasses[] = $this->owner->ContentVerticalAlign;
            }
        }

        $this->owner->extend('updateElementClasses', $classes);

        return implode(' ', $elementClasses);
    }

    public function MediaColumnClasses(): ?string
    {
        $imageClasses[] = $this->owner->getCSSFramework()->getColumnClass();

        if ($this->owner->MediaPosition === 'order-1') {
            $imageClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? 'has-order-1' : 'order-1';
        } elseif ($this->owner->MediaPosition === 'order-2') {
            $imageClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? 'has-order-2' : 'order-2';
        } else {
            $viewportName = $this->owner->getCSSFramework()->getViewportName(ElementalConfig::getDefaultViewport());
            $imageClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? sprintf('has-order-1 has-order-2-%s', $viewportName) : sprintf('order-1 order-%s-2', $viewportName);
        }

        if ($this->owner->ContentColumns) {
            if (ElementalConfig::getCSSFrameworkName() === 'bulma') {
                $imageClasses[] = 'is-' . (12 - $this->owner->ContentColumns) . '-' . $this->owner->getCSSFramework()->getViewportName(ElementalConfig::getDefaultViewport());
            } else {
                $imageClasses[] = 'col-' . $this->owner->getCSSFramework()->getViewportName(ElementalConfig::getDefaultViewport()) . '-' . (12 - $this->owner->ContentColumns);
            }
        }

        $this->owner->extend('updateMediaColumnClasses', $imageClasses);

        return implode(' ', $imageClasses);
    }

    public function MarginStyles(): string
    {
        $classes = [];

        if (!$this->owner->ContentColumns && $this->owner->MediaImage()->exists()) {
            $classes[] = 'px-5';
        }

        $this->owner->extend('updateMarginStyles', $classes);

        return implode(' ', $classes);
    }

    public function ContentColumnClasses(): string
    {
        $contentClasses[] = $this->owner->getCSSFramework()->getColumnClass();

        if ($this->owner->MediaPosition === 'order-1') {
            $contentClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? 'has-order-2' : 'order-2';
        } elseif ($this->owner->MediaPosition === 'order-2') {
            $contentClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? 'has-order-1' : 'order-1';
        } else {
            $viewportName = $this->owner->getCSSFramework()->getViewportName(ElementalConfig::getDefaultViewport());
            $contentClasses[] = ElementalConfig::getCSSFrameworkName() === 'bulma' ? sprintf('has-order-2 has-order-1-%s', $viewportName) : sprintf('order-1 order-%s-2', $viewportName);
        }

        if ($this->owner->ContentColumns && $this->owner->ExtraColumnGap) {
            $direction = str_contains($this->owner->MediaPosition, 'order-2') ? 'r' : 'l';
            $contentClasses[] = sprintf('p%s-%u-desktop', $direction, (int)$this->owner->ExtraColumnGap);
        }

        if ($this->owner->ContentColumns && $this->owner->MediaImage()->exists()) {
            $contentClasses[] = 'is-' . $this->owner->ContentColumns . '-desktop';
        } else {
            $contentClasses[] = 'is-12-desktop';
        }

        $this->owner->extend('updateContentColumnClasses', $contentClasses);

        return implode(' ', $contentClasses);
    }

    public function getColSize(): int
    {
        return $this->owner->ContentColumns ? ($this->getOwner()->config()->get('grid_column_count') - $this->owner->ContentColumns) : $this->getOwner()->config()->get('grid_column_count');
    }

    private function getCalculatedImageWidth(): int
    {
        $colSize = $this->getColSize();

        return match (true) {
            $colSize > 10 => 1440,
            $colSize > 6 => 1200,
            default => 720
        };
    }

    public function getMediaImageHeight(): int
    {
        $values = explode('x', $this->owner->MediaRatio);
        $widthRatio = $values[0];
        $heightRatio = $values[1];

        return ($this->getCalculatedImageWidth() / $widthRatio) * $heightRatio;
    }

    public function getMediaImageWidth(): int
    {
        return $this->getCalculatedImageWidth();
    }

    public function getMediaImageSourceURL(): string
    {
        if ($this->owner->MediaRatio) {
            return $this->owner->MediaImage()->FocusFill($this->getMediaImageWidth(), $this->getMediaImageHeight())->URL;
        }

        return $this->owner->MediaImage()->ScaleWidth($this->getCalculatedImageWidth())->URL;
    }
}
