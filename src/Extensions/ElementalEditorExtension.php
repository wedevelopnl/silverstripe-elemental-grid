<?php

namespace TheWebmen\ElementalGrid\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\DropdownField;

class ElementalEditorExtension extends DataExtension {

    /**
     * @param \SilverStripe\Forms\GridField\GridField $gridField
     */
    public function updateField($gridField){
        //Require extra resources
        Requirements::css('thewebmen/silverstripe-elemental-grid:resources/css/elementalgrid.css');
        Requirements::javascript('thewebmen/silverstripe-elemental-grid:resources/js/elementalgrid.js');

        //Change the config
        $config = $gridField->getConfig();
        $config->getComponentByType(GridFieldOrderableRows::class)->setImmediateUpdate(false);

        //Add editable columns
        $defaultSizeField = 'Size' . Config::forClass('TheWebmen\ElementalGrid')->get('defaultSizeField');
        $defaultSizeFieldTitle = str_replace('Size', 'Size ', $defaultSizeField);
        $defaultSizeFieldTranslateKey = strtoupper(str_replace(' ', '_', $defaultSizeFieldTitle));

        $defaultOffsetField = 'Offset' . Config::forClass('TheWebmen\ElementalGrid')->get('defaultOffsetField');
        $defaultOffsetFieldTitle = str_replace('Size', 'Size ', $defaultOffsetField);
        $defaultOffsetFieldTranslateKey = strtoupper(str_replace(' ', '_', $defaultOffsetFieldTitle));

        $editableColumns = new \Symbiote\GridFieldExtensions\GridFieldEditableColumns();
        $editableColumns->setDisplayFields(array(
            $defaultSizeField => array(
                'title' => _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultSizeFieldTranslateKey, $defaultSizeFieldTitle),
                'callback' => function($record, $column, $grid) use($defaultSizeField, $defaultSizeFieldTranslateKey, $defaultSizeFieldTitle) {
                    return DropdownField::create($defaultSizeField, _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultSizeFieldTranslateKey, $defaultSizeFieldTitle), BaseElementExtension::getColSizeOptions())
                        ->addExtraClass('grideditor-sizefield')
                        ->setAttribute('data-title', _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultSizeFieldTranslateKey, $defaultSizeFieldTitle));
                }
            ),
            $defaultOffsetField => array(
                'title' => _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultOffsetFieldTranslateKey, $defaultOffsetFieldTitle),
                'callback' => function($record, $column, $grid) use($defaultOffsetField, $defaultOffsetFieldTranslateKey, $defaultOffsetFieldTitle) {
                    return DropdownField::create($defaultOffsetField, _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultOffsetFieldTranslateKey, $defaultOffsetFieldTitle), BaseElementExtension::getColSizeOptions(false, true))
                        ->addExtraClass('grideditor-offsetfield')
                        ->setAttribute('data-title', _t('TheWebmen\ElementalGrid\Extensions\BaseElementExtension.' . $defaultOffsetFieldTranslateKey, $defaultOffsetFieldTitle));
                }
            ),
            'BlockType' => array(
                'callback' => function($record, $column, $grid) {
                    return HiddenField::create('BlockType', 'BlockType');
                }
            )
        ));
        $config->addComponent($editableColumns);

        //Add extra class
        $gridField->addExtraClass('grideditor');
    }

}
