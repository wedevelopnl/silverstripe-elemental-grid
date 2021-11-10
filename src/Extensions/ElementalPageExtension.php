<?php

namespace TheWebmen\ElementalGrid\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

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

    /***
     * @param FieldList $fields
     */
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
            CheckboxField::create('UseElementalGrid', _t('ElementalGrid.UseElementalGrid.Label', 'Use grid on this page'))
                ->setDescription(_t('ElementalGrid.UseElementalGrid.Description', 'Make sure to save this page right after changing this setting'))
        );
    }
}
