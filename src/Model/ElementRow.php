<?php

namespace TheWebmen\ElementalGrid\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Config\Config;

//use TheWebmen\ElementalGrid\Controllers\ElementRowController;

class ElementRow extends BaseElement
{
    private static string $icon = 'font-icon-menu';

    private static string $table_name = 'ElementRow';

    private static string $singular_name = 'row';

    private static string $plural_name = 'rows';

    private static string $description = 'Row element';

//    private static string $controller_class = ElementRowController::class;

    private static string $block_type = 'full-width';

    public function getCMSFields(): FieldList
    {
        $fields =  parent::getCMSFields();

        $fields->removeByName('Column');

        return $fields;
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
        switch (Config::forClass('TheWebmen\ElementalGrid')->get('cssFramework')) {
            case 'bulma':
                return 'columns is-multiline';
                break;
            default:
                return 'row';
        }
    }
}
