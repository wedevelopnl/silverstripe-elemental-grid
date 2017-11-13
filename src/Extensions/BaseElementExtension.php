<?php

namespace TWM\ElementalGrid\Extensions;

class BaseElementExtension extends \SilverStripe\ORM\DataExtension {

    private static $db = array(
        'SizeMD' => 'Int'
    );

    public function updateCMSFields(\SilverStripe\Forms\FieldList $fields)
    {
        $fields->addFieldToTab('Root.Column', \SilverStripe\Forms\DropdownField::create('SizeMD', 'Size', array(
            '1' => 'Col 1',
            '2' => 'Col 2',
            '3' => 'Col 3',
            '4' => 'Col 4',
            '5' => 'Col 5',
            '6' => 'Col 6',
            '7' => 'Col 7',
            '8' => 'Col 8',
            '9' => 'Col 9',
            '10' => 'Col 10',
            '11' => 'Col 11',
            '12' => 'Col 12'
        )));
        parent::updateCMSFields($fields);
    }

}
