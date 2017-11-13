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
        //Require extra resources
        Requirements::css('twm/silverstripe-elemental-grid:resources/css/elementalgrid.css');
        Requirements::javascript('twm/silverstripe-elemental-grid:resources/js/elementalgrid.js');

        //Change the config
        $config = $gridField->getConfig();
        $config->getComponentByType(GridFieldOrderableRows::class)->setImmediateUpdate(false);

        //Add editable columns
        $editableColumns = new \Symbiote\GridFieldExtensions\GridFieldEditableColumns();
        $editableColumns->setDisplayFields(array(
            'SizeMD' => array(
                'title' => _t('TWM\ElementalGrid\Extensions\BaseElementExtension.SIZE_MD', 'Size'),
                'callback' => function($record, $column, $grid) {
                    return \SilverStripe\Forms\DropdownField::create('SizeMD', _t('TWM\ElementalGrid\Extensions\BaseElementExtension.SIZE_MD', 'Size'), BaseElementExtension::getColSizeOptions())->addExtraClass('grideditor-sizefield');
                }
            )
        ));
        $config->addComponent($editableColumns);

        //Add extra class
        $gridField->addExtraClass('grideditor');
    }

}
