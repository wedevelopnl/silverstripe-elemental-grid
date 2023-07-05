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
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataExtension;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * @property string $MediaPosition
 * @property string $ContentColumns
 * @property string $ContentVerticalAlign
 * @method Image ContentImage()
 * @property ElementContent|ElementContentExtension $owner
 */
final class ElementContentExtension extends DataExtension
{
    private static array $db = [
        'MediaPosition' => 'Varchar(64)',
        'ContentColumns' => 'Varchar(64)',
        'ContentVerticalAlign' => 'Varchar(64)',
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
        'has-order-2 has-order-1-touch' => 'Last on desktop but first on mobile devices',
    ];

    /** @config */
    private static array $defaults = [
        'ContentVerticalAlign' => 'is-align-items-center',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName([
            'MediaPosition',
            'ContentImage',
            'ContentColumns',
            'ContentVerticalAlign',
        ]);

        $fields->insertBefore('Settings', new Tab('Media'));

        $fields->addFieldsToTab('Root.Media', [
            UploadField::create('ContentImage', 'Image')
                ->setFolderName('ContentImages')
                ->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']),
            OptionsetField::create('ContentColumns', 'Content width', self::$contentColumns)
                ->setTemplate('WeDevelop/ElementalGrid/Forms/OptionsetImageField')
                ->addExtraClass('optionset-image-field'),
            DropdownField::create('MediaPosition', 'Media position', self::$imagePositions),
            Wrapper::create([
                DropdownField::create(
                    'ContentVerticalAlign',
                    'Vertical align (if horizontal)',
                    self::$contentVerticalAligments
                ),
                CheckboxField::create('ShowPCBOCircle', 'Show PCBO circle on top of the image'),
                CheckboxField::create('ShowWatermark', 'Show watermark behind the image'),
            ])->displayIf('ContentColumns')->isNotEmpty()->end(),
        ]);
    }

    public function ElementStyles(): string
    {
        $elementClasses = [
            'is-multiline',
            'is-flex',
            'has-gap-medium-desktop',
        ];

        if ($this->owner->ContentVerticalAlign && $this->owner->ContentColumns) {
            $elementClasses[] = $this->owner->ContentVerticalAlign;
        }

        if (!$this->owner->ContentColumns && !$this->owner->ContentImage()->exists()) {
            $elementClasses[] = 'm-0';
            $elementClasses[] = 'has-height-100';
        }

        $elementClasses = implode(' ', $elementClasses);

        $this->owner->extend('updateElementStyles', $classes);

        return $elementClasses;
    }

    public function ImageStyles(): ?string
    {
        $imageClasses = [];

        if ($this->owner->MediaPosition) {
            $imageClasses[] = $this->owner->MediaPosition;
        }

        if ($this->owner->ContentColumns) {
            $imageClasses[] = 'is-' . (12 - $this->owner->ContentColumns) . '-desktop';
        }

        $this->owner->extend('updateImageStyles', $classes);

        return implode(' ', $imageClasses);
    }

    public function getColSize(): int
    {
        return $this->owner->ContentColumns ? (12 - $this->owner->ContentColumns) : 12;
    }

    public function MarginStyles(): string
    {
        $classes = [];

        if (!$this->owner->ContentColumns && $this->owner->ContentImage()->exists()) {
            $classes[] = 'px-5';
        }

        $this->owner->extend('updateMarginStyles', $classes);

        return implode(' ', $classes);
    }

    public function ContentStyles(): string
    {
        $contentClasses = [];

        if ($this->owner->MediaPosition !== 'has-order-1') {
            $contentClasses[] = 'has-order-1';
        } else {
            $contentClasses[] = 'has-order-2';
        }

        if ($this->owner->ContentColumns && $this->owner->ContentImage()->exists()) {
            $contentClasses[] = 'is-' . $this->owner->ContentColumns . '-desktop';
        } else {
            $contentClasses[] = 'is-12-desktop';
            $contentClasses[] = 'p-0';
        }

        $this->owner->extend('updateContentStyles', $contentClasses);

        return implode(' ', $contentClasses);
    }
}
