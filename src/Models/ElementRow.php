<?php

namespace Webmen\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\FieldList;
use Webmen\ElementalGrid\Controllers\ElementRowController;

/***
 * Class ElementRow
 * @package Webmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class ElementRow extends BaseElement
{
    private static string $icon = 'font-icon-menu';

    private static string $table_name = 'ElementRow';

    private static string $singular_name = 'row';

    private static string $plural_name = 'rows';

    private static string $description = 'Row element';

    private static string $controller_class = ElementRowController::class;

    private static string $block_type = 'full-width';

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Column');

        return $fields;
    }

    public function getType(): string
    {
        return _t(__CLASS__ . '.BlockType', 'Row');
    }

    public function getRowClasses(): string
    {
        return $this->owner->ExtraClass ? implode(' ', [$this->getCSSFramework()->getRowClasses(), $this->owner->ExtraClass]) : $this->getCSSFramework()->getRowClasses();
    }

    public function getFluidContainerClass(): string
    {
        return $this->getCSSFramework()->getFluidContainerClass();
    }
}
