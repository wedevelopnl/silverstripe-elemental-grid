<?php

namespace TheWebmen\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use TheWebmen\ElementalGrid\Controllers\ElementRowController;

/***
 * Class ElementRow
 * @package TheWebmen\ElementalGrid\Extensions
 *
 * @property BaseElement $owner
 */
class ElementRow extends BaseElement
{
    /**
     * @var string
     */
    private static $icon = 'font-icon-menu';

    /**
     * @var string
     */
    private static $table_name = 'ElementRow';

    /**
     * @var string
     */
    private static $singular_name = 'row';

    /**
     * @var string
     */
    private static $plural_name = 'rows';

    /**
     * @var string
     */
    private static $description = 'Row element';

    /**
     * @var string
     */
    private static $controller_class = ElementRowController::class;

    /**
     * @var string
     */
    private static $block_type = 'full-width';

    /**
     * @var array|string[]
     */
    private static $db = [
        'IsFluid' => 'Boolean'
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Column');

        if (!$fields->fieldPosition('Title')) {
            $fields->removeByName('TitleTag');
            $fields->removeByName('TitleClass');
        }

        $fields->addFieldsToTab('Root.Main', [
            CheckboxField::create('IsFluid', 'The row uses the full width of the page'),
        ]);

        return $fields;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Row');
    }

    /**
     * @return string
     */
    public function getRowClasses()
    {
        return $this->owner->ExtraClass ? implode(' ', [$this->getCSSFramework()->getRowClasses(), $this->owner->ExtraClass]) : $this->getCSSFramework()->getRowClasses();
    }

    /**
     * @return string
     */
    public function getFluidContainerClass()
    {
        return $this->getCSSFramework()->getFluidContainerClass();
    }
}
