<?php

namespace Webmen\ElementalGrid\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/***
 * Class ElementContent
 * @package Webmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class ElementContentExtension extends DataExtension
{
    private static array $db = [
        'TitlePriority' => 'Varchar(100)',
        'TitleSize' => 'Varchar(100)',
    ];

    private static array $titleOptions = [
        'div' => 'Default',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('TitlePriority', 'Title priority', self::$titleOptions),
            DropdownField::create('TitleSize', 'Title size', self::$titleOptions),
        ]);
    }

    public function getTitleSizeClass(): string
    {
        return $this->owner->getCSSFramework()->getTitleSizeClass();
    }

}
