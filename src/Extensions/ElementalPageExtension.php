<?php

namespace TheWebmen\ElementalGrid\Extensions;

use DNADesign\Elemental\Forms\ElementalAreaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataExtension;
use UncleCheese\DisplayLogic\Extensions\DisplayLogic;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class ElementalPageExtension extends DataExtension
{
    /***
     * @var bool
     */
    private static $elemental_keep_content_field = true;

    /***
     * @var array
     */
    private static $db = [
        'UseElementalGrid' => 'Boolean'
    ];

    /***
     * @var array
     */
    private static $defaults = [
        'UseElementalGrid' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        if ($this->owner->UseElementalGrid) {
            $fields->removeByName('Content');
            $insertBefore = 'ElementalArea';
        } else {
            $fields->removeByName('ElementalArea');
            $insertBefore = 'Content';
        }

        $fields->insertBefore(
            $insertBefore,
            CheckboxField::create('UseElementalGrid', 'Use grid on this page')
                ->setDescription('Make sure to save this page right after changing this setting')
        );
    }
}
