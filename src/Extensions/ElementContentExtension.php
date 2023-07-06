<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\ElementContent;
use gorriecoe\Link\Models\Link;
use gorriecoe\LinkField\LinkField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use WeDevelop\MediaField\Form\MediaField;

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
        'is-align-items-center' => 'Center (default)',
        'is-align-items-flex-end' => 'Bottom',
    ];

    private static array $imagePositions = [
        'has-order-1' => 'Always first (default)',
        'has-order-2' => 'Always last',
        'has-order-2 has-order-1-touch' => 'Last on desktop, first on mobile/tablet devices',
    ];

    private static array $columnGaps = [
        '' => 'None (default)',
        '5' => 'Small',
        '7' => 'Normal',
        '9' => 'Medium',
        '11' => 'Large',
    ];

    private static array $defaults = [
        'ContentVerticalAlign' => 'is-align-items-center',
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
        $mediaField->setTitle('Video settings');
        $mediaField->getVideoWrapper()->push(
            UploadField::create('MediaVideoCustomThumbnail', 'Custom video thumbnail')
                ->setFolderName('MediaUploads')
                ->setDescription('This overwrites the default thumbnail provided by youtube or vimeo'),
        );

        $fields->addFieldsToTab('Root.Media', [
            $mediaField,
            Wrapper::create([
                CheckboxField::create('MediaVideoHasOverlay', 'Show overlay on top of video thumbnail'),
            ])->displayIf('MediaType')->isEqualTo('video')->end(),
            TextField::create('MediaCaption', 'Caption text'),
            DropdownField::create('MediaRatio', 'Media ratio', self::$mediaRatios)
                ->setEmptyString('Auto (default)')
                ->setDescription('By default, \'Auto\' will make videos appear as 16x9 ratio, while images will be shown as they are'),

            OptionsetField::create('ContentColumns', 'Content width', self::$contentColumns)
                ->setTemplate('WeDevelop/ElementalGrid/Forms/OptionsetImageField')
                ->addExtraClass('optionset-image-field'),
            DropdownField::create('MediaPosition', 'Media position', self::$imagePositions),
            Wrapper::create([
                DropdownField::create('ContentVerticalAlign', 'Vertical align (if horizontal)', self::$contentVerticalAligments),
                DropdownField::create('ExtraColumnGap', 'Extra column gap size', self::$columnGaps),
            ])->displayIf('ContentColumns')->isNotEmpty()->end(),
        ]);


        if ($this->owner->MediaType === 'video') {
            $fields->addFieldsToTab('Root.VideoEmbeddedData', [
                ReadonlyField::create('MediaVideoEmbeddedURL', 'Shortened URL'),
                ReadonlyField::create('MediaVideoProvider', 'Video provider'),
                ReadonlyField::create('MediaVideoEmbeddedName', 'Embedded name'),
                ReadonlyField::create('MediaVideoEmbeddedDescription', 'Embedded description'),
                ReadonlyField::create('MediaVideoEmbeddedThumbnail', 'Embedded thumbnail URL'),
                FieldGroup::create([
                    LiteralField::create('MediaVideoEmbeddedThumbnailPreview', '<img src="' . $this->owner->MediaVideoEmbeddedThumbnail . '">', 'Embedded thumbnail'),
                ])->setTitle('Video Thumbnail'),
                ReadonlyField::create('MediaVideoEmbeddedCreated', 'Embedded publication date'),
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
        $elementClasses = [];

        $elementClasses[] = 'columns is-multiline';

        if ($this->owner->ContentColumns && $this->owner->ContentVerticalAlign) {
            $elementClasses[] = $this->owner->ContentVerticalAlign;
        }

        $this->owner->extend('updateElementClasses', $classes);

        return implode(' ', $elementClasses);
    }

    public function MediaColumnClasses(): ?string
    {
        $imageClasses = [];

        $imageClasses[] = 'column';

        if ($this->owner->MediaPosition) {
            $imageClasses[] = $this->owner->MediaPosition;
        }

        if ($this->owner->ContentColumns) {
            $imageClasses[] = 'is-' . (12 - $this->owner->ContentColumns) . '-desktop';
        }

        $this->owner->extend('updateMediaColumnClasses', $classes);

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
        $contentClasses = [];

        $contentClasses[] = 'column';

        if ($this->owner->MediaPosition !== 'has-order-1') {
            $contentClasses[] = 'has-order-1';
        } else {
            $contentClasses[] = 'has-order-2';
        }

        if ($this->owner->ContentColumns && $this->owner->ExtraColumnGap) {
            $direction = str_contains($this->owner->MediaPosition, 'has-order-2') ? 'r' : 'l';
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
}
