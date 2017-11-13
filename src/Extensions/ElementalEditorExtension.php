<?php

namespace TWM\ElementalGrid\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementalEditorExtension extends DataExtension {

    /**
     * @param \SilverStripe\Forms\GridField\GridField $gridField
     */
    public function updateField($gridField){
        Requirements::css('twm/silverstripe-elemental-grid:resources/css/elementalgrid.css');
        Requirements::javascript('twm/silverstripe-elemental-grid:resources/js/elementalgrid.js');

        $config = $gridField->getConfig();
        $config->getComponentByType(GridFieldOrderableRows::class)->setImmediateUpdate(false);

        $editableColumns = new \Symbiote\GridFieldExtensions\GridFieldEditableColumns();
        $editableColumns->setDisplayFields(array(
            'SizeMD' => array(
                'title' => 'Column size',
                'callback' => function($record, $column, $grid) {
                    return \SilverStripe\Forms\DropdownField::create('SizeMD', 'Size', array(
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
                    ))->addExtraClass('grideditor-sizefield');
                }
            )
        ));
        $config->addComponent($editableColumns);

        $gridField->addExtraClass('grideditor');
    }

}
