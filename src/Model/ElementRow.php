<?php

namespace TheWebmen\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Config\Config;
use TheWebmen\ElementalGrid\Controllers\ElementRowController;

class ElementRow extends BaseElement
{
    private static $icon = 'font-icon-menu';

    private static $table_name = 'ElementRow';

    private static $singular_name = 'row';

    private static $plural_name = 'rows';

    private static $description = 'Row element';

    private static $controller_class = ElementRowController::class;

    private static $block_type = 'full-width';

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName('TitleAndDisplayed');
            $fields->addFieldToTab('Root.Main', TextField::create('Title'));
        });

        return parent::getCMSFields();
    }

    public function getSummary()
    {
        return '';
    }

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Row');
    }
    
    public function RowClass()
    {
        switch (Config::forClass('TheWebmen\ElementalGrid')->get('cssFramework')){
            case 'bulma':
                return 'columns is-multiline';
                break;
            default:
                return 'row';
        }
    }

}
