<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Configurable;
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
use WeDevelop\ElementalGrid\CSSFramework\CSSFrameworkInterface;
use WeDevelop\ElementalGrid\CSSFramework\TailwindCSSFramework;
use WeDevelop\ElementalGrid\ElementalConfig;
use WeDevelop\MediaField\Form\MediaField;

/**
 * @method Image MediaImage()
 * @property ElementContent|ElementContentExtension $owner
 */
final class ElementContentExtension extends DataExtension
{
    use Configurable;

    private static array $db = [
        'ContentColumns' => 'Varchar(64)',
        'ContentVerticalAlign' => 'Varchar(64)',
        'ExtraColumnGap' => 'Int(3)',

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
        '' => 'Top (default)',
        'align-items-center' => 'Center',
        'align-items-end' => 'Bottom',
    ];

    private static array $imagePositions = [
        'order-1' => 'Always first (default)',
        'order-2' => 'Always last',
        'order-1 order-md-2' => 'Last on desktop, first on mobile/tablet devices',
    ];

    private static array $columnGaps = [
        '' => 'None (default)',
        '2' => 'Smallest',
        '3' => 'Smaller',
        '5' => 'Small',
        '7' => 'Normal',
        '9' => 'Medium',
        '11' => 'Large',
        '16' => 'Larger',
        '17' => 'Largest',
    ];

    private static array $defaults = [
        'ContentVerticalAlign' => 'align-items-center',
        'MediaPosition' => 'order-1',
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
            HeaderField::create('MediaFileTitle', _t(__CLASS__ . '.MEDIA_FILE', 'Media file'))->setHeadingLevel(1),
            $mediaField,
            Wrapper::create([
                CheckboxField::create('MediaVideoHasOverlay', _t(__CLASS__ . '.SHOW_OVERLAY', 'Show dark overlay on top of video thumbnail')),
            ])->displayIf('MediaType')->isEqualTo(MediaField::TYPE_VIDEO)->end(),
            TextField::create('MediaCaption', _t(__CLASS__ . '.CAPTION_TEXT', 'Caption text')),
            DropdownField::create('MediaRatio', _t(__CLASS__ . '.MEDIA_RATIO', 'Media ratio'), static::config()->get('mediaRatios'))
                ->setEmptyString(_t(__CLASS__ . '.AUTO_DEFAULT', 'Auto (default)'))
                ->setDescription(_t(__CLASS__ . '.DEFAULT_RATIO_DESCRIPTION', 'By default, \'Auto\' will make videos appear as 16x9 ratio, while images will be shown as they are')),
        ]);

        $fields->addFieldsToTab('Root.Media', [
            HeaderField::create('MediaPositioningTitle', _t(__CLASS__ . '.MEDIA_POSITIONING', 'Media positioning'))->setHeadingLevel(1),
            OptionsetField::create('ContentColumns', _t(__CLASS__ . '.CONTENT_COLUMN_WIDTH', 'Width of the content column'), self::$contentColumns)
                ->setTemplate('WeDevelop/ElementalGrid/Forms/OptionsetImageField')
                ->addExtraClass('optionset-image-field'),
            DropdownField::create('MediaPosition', _t(__CLASS__ . '.MEDIA_POSITION', 'Media position'), self::$imagePositions),
            Wrapper::create([
                DropdownField::create('ContentVerticalAlign', _t(__CLASS__ . '.VERTICAL_ALIGNMENT', 'Vertical alignment'), self::$contentVerticalAligments),
                DropdownField::create('ExtraColumnGap', _t(__CLASS__ . '.EXTRA_COLUMN_GAP_SIZE', 'Extra column gap size'), static::config()->get('columnGaps')),
            ])->displayIf('ContentColumns')->isNotEmpty()->end(),
        ]);


        if ($this->getOwner()->MediaType === MediaField::TYPE_VIDEO) {
            $fields->addFieldsToTab('Root.VideoEmbeddedData', [
                ReadonlyField::create('MediaVideoEmbeddedURL', _t(__CLASS__ . '.SHORTENED_URL', 'Shortened URL')),
                ReadonlyField::create('MediaVideoProvider', _t(__CLASS__ . '.VIDEO_PROVIDER', 'Video provider')),
                ReadonlyField::create('MediaVideoEmbeddedName', _t(__CLASS__ . '.EMBEDDED_NAME', 'Embedded name')),
                ReadonlyField::create('MediaVideoEmbeddedDescription', _t(__CLASS__ . '.EMBEDDED_DESCRIPTION', 'Embedded description')),
                ReadonlyField::create('MediaVideoEmbeddedThumbnail', _t(__CLASS__ . '.EMBEDDED_THUMBNAIL_URL', 'Embedded thumbnail URL')),
                FieldGroup::create([
                    LiteralField::create('MediaVideoEmbeddedThumbnailPreview', '<img src="' . $this->getOwner()->MediaVideoEmbeddedThumbnail . '">', _t(__CLASS__ . '.EMBEDDED_THUMBNAIL', 'Embedded thumbnail')),
                ])->setTitle(_t(__CLASS__ . '.VIDEO_THUMBNAIL', 'Video Thumbnail')),
                ReadonlyField::create('MediaVideoEmbeddedCreated', _t(__CLASS__ . '.EMBEDDED_PUBLICATION_DATE', 'Embedded publication date')),
            ]);
        }
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if ($this->getOwner()->MediaType === MediaField::TYPE_VIDEO && $this->getOwner()->MediaVideoFullURL) {
            $this->getOwner()->MediaVideoFullURL = trim($this->getOwner()->MediaVideoFullURL);
            MediaField::saveEmbed($this->getOwner());
        }
    }

    public function getMediaRatioClass(): ?string
    {
        $mediaRatio = $this->getOwner()->MediaRatio;

        if (!$this->getOwner()->MediaRatio && $this->getOwner()->MediaType === MediaField::TYPE_VIDEO) {
            $mediaRatio = '16x9';
        }

        return $this->getCSSFramework()->getMediaRatioClass($mediaRatio);
    }

    public function ElementClasses(): string
    {
        $elementClasses = [];

        $elementClasses[] = $this->getCSSFramework()->getRowClasses();

        if ($this->getOwner()->ContentColumns && $this->getOwner()->ContentVerticalAlign) {
            if (ElementalConfig::getCSSFrameworkName() === 'bulma') {
                $elementClasses[] = 'is-' . $this->getOwner()->ContentVerticalAlign;
            } else if(ElementalConfig::getCSSFrameworkName() === 'tailwind') {
                $elementClasses[] = str_replace('align-', '', $this->getOwner()->ContentVerticalAlign);
            } else {
                $elementClasses[] = $this->getOwner()->ContentVerticalAlign;
            }
        }

        $this->getOwner()->extend('updateElementClasses', $elementClasses);

        return implode(' ', $elementClasses);
    }

    public function MediaColumnClasses(): ?string
    {
        if ($this->getCSSFramework()->getInitialContentColumnClass()) {
            $imageClasses[] = $this->getCSSFramework()->getInitialContentColumnClass();
        }

        if ($this->getCSSFramework()->getMediaColumnWidthClass($this->owner->ContentColumns)) {
            $imageClasses[] = $this->getCSSFramework()->getMediaColumnWidthClass($this->owner->ContentColumns);
        }

        $mediaPosition = $this->getOwner()->MediaPosition ?: self::$defaults['MediaPosition'];
        
        if ($this->getCSSFramework()->getMediaColumnOrderClasses($mediaPosition)) {
            $imageClasses[] = $this->getCSSFramework()->getMediaColumnOrderClasses($mediaPosition);
        }

        $this->getOwner()->extend('updateMediaColumnClasses', $imageClasses);

        return implode(' ', $imageClasses);
    }

    public function ContentClasses(): string
    {
        $contentClasses[] = 'content';

        if ($this->getOwner()->ContentColumns && $this->getOwner()->ExtraColumnGap) {
            $contentClasses[] = $this->getCSSFramework()->getContentPaddingClass($this->getContentPaddingDirection(), $this->getOwner()->ExtraColumnGap);
        }

        $this->getOwner()->extend('updateContentClasses', $contentClasses);

        return implode(' ', $contentClasses);
    }

    public function ContentColumnClasses(): string
    {
        if ($this->getCSSFramework()->getInitialContentColumnClass()) {
            $contentClasses[] = $this->getCSSFramework()->getInitialContentColumnClass();
        }

        if ($this->getCSSFramework()->getContentColumnWidthClass($this->owner->ContentColumns)) {
            $contentClasses[] = $this->getCSSFramework()->getContentColumnWidthClass($this->owner->ContentColumns);
        }

        if ($this->getCSSFramework()->getContentColumnOrderClasses($this->getOwner()->MediaPosition)) {
            $contentClasses[] = $this->getCSSFramework()->getContentColumnOrderClasses($this->getOwner()->MediaPosition);
        }

        $this->getOwner()->extend('updateContentColumnClasses', $contentClasses);

        return implode(' ', $contentClasses);
    }

    public function getColSize(): int
    {
        return $this->getOwner()->ContentColumns ? (ElementalConfig::getGridColumnCount() - $this->getOwner()->ContentColumns) : ElementalConfig::getGridColumnCount();
    }

    private function getCalculatedMediaImageWidth(): int
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
        if ($this->getOwner()->MediaRatio) {
            $values = explode('x', $this->getOwner()->MediaRatio);
            $widthRatio = $values[0];
            $heightRatio = $values[1];

            return ($this->getCalculatedMediaImageWidth() / $widthRatio) * $heightRatio;
        }

        return $this->getOwner()->MediaImage()->ScaleWidth($this->getCalculatedMediaImageWidth())->Height;
    }

    public function getMediaImageWidth(): int
    {
        return $this->getCalculatedMediaImageWidth();
    }

    public function getMediaImageSourceURL(): ?string
    {
        if ($this->getOwner()->MediaRatio) {
            return $this->getOwner()->MediaImage()->FocusFill($this->getMediaImageWidth(), $this->getMediaImageHeight())?->URL;
        }

        return $this->getOwner()->MediaImage()->ScaleWidth($this->getCalculatedMediaImageWidth())?->URL;
    }

    private function getCSSFramework(): CSSFrameworkInterface
    {
        return $this->getOwner()->getCSSFramework();
    }

    private function getContentPaddingDirection(): string
    {
        if (str_contains($this->getOwner()->MediaPosition, 'order-2') || str_contains($this->getOwner()->MediaPosition, 'order-md-2')) {
            return CSSFrameworkInterface::DIRECTION_RIGHT;
        }

        return CSSFrameworkInterface::DIRECTION_LEFT;
    }
}
