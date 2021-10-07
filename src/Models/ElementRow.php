<?php

namespace TheWebmen\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
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
        'IsFluid' => 'Boolean',
        'CustomSectionClass' => 'Varchar(255)',
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Column');

        $fields->renameField('ExtraClass', 'Custom row classes');

        $fields->addFieldsToTab('Root.Settings', [
            TextField::create('CustomSectionClass', 'Custom section classes'),
        ]);

        if (!$fields->fieldPosition('FullWidth')) {
            $fields->addFieldsToTab('Root.Main', [
                CheckboxField::create('IsFluid', 'The row uses the full width of the page'),
            ]);
        }

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
        $classes = [];

        array_push($classes, $this->getCSSFramework()->getRowClasses());

        $this->extend('updateRowClasses', $classes);

        if ($this->owner->ExtraClass) {
            array_push($classes, $this->owner->ExtraClass);
        }

        return implode(' ', $classes);
    }


    /**
     * @return string
     */
    public function getSectionClasses()
    {
        $classes = [];

        $this->extend('updateSectionClasses', $classes);

        if ($this->owner->CustomSectionClass) {
            array_push($classes, $this->owner->CustomSectionClass);
        }

        return implode(' ', $classes);
    }

    /**
     * @return string
     */
    public function getContainerClasses()
    {
        $classes = [];

        array_push($classes, $this->getCSSFramework()->getContainerClass($this->IsFluid));

        $this->extend('updateContainerClasses', $classes);

        return implode(' ', $classes);
    }
}
