<?php

namespace TheWebmen\ElementalGrid\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;

class BaseElementExtension extends \SilverStripe\ORM\DataExtension {

    /**
     * @config
     */
    private static $num_columns = 12;
    
    /**
     * @config
     */
    private static $default_col_size = 6;

    /**
     * @var array
     */
    private static $db = array(
        'SizeXS' => 'Int',
        'SizeSM' => 'Int',
        'SizeMD' => 'Int',
        'SizeLG' => 'Int',

        'OffsetXS' => 'Int',
        'OffsetSM' => 'Int',
        'OffsetMD' => 'Int',
        'OffsetLG' => 'Int',

        'VisibilityXS' => 'Varchar',
        'VisibilitySM' => 'Varchar',
        'VisibilityMD' => 'Varchar',
        'VisibilityLG' => 'Varchar',

        'BlockType' => 'Varchar'
    );

    public function populateDefaults()
    {
        $defaultSizeField = 'Size' . Config::forClass('TheWebmen\ElementalGrid')->get('defaultSizeField');
        $this->owner->$defaultSizeField = Config::forClass('TheWebmen\ElementalGrid\Extensions\BaseElementExtension')->get('default_col_size');
    }

    /**
     * Get the options for col sizes
     * @return array
     */
    public static function getColSizeOptions($includeDefault = false, $includeNone = false){
        $config = Config::inst()->get(__CLASS__);
        $numColumns = $config['num_columns'];
        $out = array();
        if($includeDefault){
            $out[0] = _t(__CLASS__ . '.DEFAULT', 'Default');
        }else if($includeNone){
            $out[0] = _t(__CLASS__ . '.NONE', 'None');
        }
        for($i = 1; $i < $numColumns + 1; $i++){
            $out[$i] = _t(__CLASS__ . '.COLUMN', 'Column') . ' ' . $i . '/' . $numColumns;
        }
        return $out;
    }

    /**
     * @return array
     */
    public static function getColVisibilityOptions(){
        $out = array();
        $out['default'] = _t(__CLASS__ . '.DEFAULT', 'Default');
        $out['visible'] = _t(__CLASS__ . '.VISIBLE', 'Visible');
        $out['hidden'] = _t(__CLASS__ . '.HIDDEN', 'Hidden');
        return $out;
    }

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(\SilverStripe\Forms\FieldList $fields)
    {
        $fields->removeByName('BlockType');
        if( $this->getBlockType() == 'full-width' ) {
            $fields->removeByName('SizeXS');
            $fields->removeByName('SizeSM');
            $fields->removeByName('SizeMD');
            $fields->removeByName('SizeLG');
            $fields->removeByName('OffsetXS');
            $fields->removeByName('OffsetSM');
            $fields->removeByName('OffsetMD');
            $fields->removeByName('OffsetLG');
            $fields->removeByName('VisibilityXS');
            $fields->removeByName('VisibilitySM');
            $fields->removeByName('VisibilityMD');
            $fields->removeByName('VisibilityLG');
        } else {
            $fields->findOrMakeTab('Root.Column', _t(__CLASS__ . '.COLUMN', 'Column'));
            $fields->addFieldToTab('Root.Column', HeaderField::create('HeadingXS', _t(__CLASS__ . '.XS', 'XS')));
            $fields->addFieldToTab('Root.Column', DropdownField::create('SizeXS', _t(__CLASS__ . '.SIZE_XS', 'Size XS'), self::getColSizeOptions(true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('OffsetXS', _t(__CLASS__ . '.OFFSET_XS', 'Offset XS'), self::getColSizeOptions(false, true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('VisibilityXS', _t(__CLASS__ . '.VISIBILITY_XS', 'Visibility XS'), self::getColVisibilityOptions()));

            $fields->addFieldToTab('Root.Column', HeaderField::create('HeadingSM', _t(__CLASS__ . '.SM', 'SM')));
            $fields->addFieldToTab('Root.Column', DropdownField::create('SizeSM', _t(__CLASS__ . '.SIZE_SM', 'Size SM'), self::getColSizeOptions(true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('OffsetSM', _t(__CLASS__ . '.OFFSET_SM', 'Offset SM'), self::getColSizeOptions(false, true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('VisibilitySM', _t(__CLASS__ . '.VISIBILITY_SM', 'Visibility SM'), self::getColVisibilityOptions()));

            $fields->addFieldToTab('Root.Column', HeaderField::create('HeadingMD', _t(__CLASS__ . '.MD', 'MD')));
            $fields->addFieldToTab('Root.Column', DropdownField::create('SizeMD', _t(__CLASS__ . '.SIZE_MD', 'Size MD'), self::getColSizeOptions(true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('OffsetMD', _t(__CLASS__ . '.OFFSET_MD', 'Offset MD'), self::getColSizeOptions(false, true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('VisibilityMD', _t(__CLASS__ . '.VISIBILITY_MD', 'Visibility MD'), self::getColVisibilityOptions()));

            $fields->addFieldToTab('Root.Column', HeaderField::create('HeadingLG', _t(__CLASS__ . '.LG', 'LG')));
            $fields->addFieldToTab('Root.Column', DropdownField::create('SizeLG', _t(__CLASS__ . '.SIZE_LG', 'Size LG'), self::getColSizeOptions(true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('OffsetLG', _t(__CLASS__ . '.OFFSET_LG', 'Offset LG'), self::getColSizeOptions(false, true)));
            $fields->addFieldToTab('Root.Column', DropdownField::create('VisibilityLG', _t(__CLASS__ . '.VISIBILITY_LG', 'Visibility LG'), self::getColVisibilityOptions()));
        }
        parent::updateCMSFields($fields);
    }

    /**
     * @return string
     */
    public function BootstrapColClasses(){
        //Col options
        $classes = '';
        if($this->owner->SizeXS){
            $classes .= ' col-xs-' . $this->owner->SizeXS;
        }
        if($this->owner->SizeSM){
            $classes .= ' col-sm-' . $this->owner->SizeSM;
        }
        if($this->owner->SizeMD){
            $classes .= ' col-md-' . $this->owner->SizeMD;
        }
        if($this->owner->SizeLG){
            $classes .= ' col-lg-' . $this->owner->SizeLG;
        }
        //Offset options
        if($this->owner->OffsetXS){
            $classes .= ' col-xs-offset-' . $this->owner->OffsetXS;
        }
        if($this->owner->OffsetSM){
            $classes .= ' col-sm-offset-' . $this->owner->OffsetSM;
        }
        if($this->owner->OffsetMD){
            $classes .= ' col-md-offset-' . $this->owner->OffsetMD;
        }
        if($this->owner->OffsetLG){
            $classes .= ' col-lg-offset-' . $this->owner->OffsetLG;
        }
        //Visibility options
        if($this->owner->VisibilityXS && $this->owner->VisibilityXS != 'default'){
            $classes .= ' ' . $this->owner->VisibilityXS . '-xs';
        }
        if($this->owner->VisibilitySM && $this->owner->VisibilitySM != 'default'){
            $classes .= ' ' . $this->owner->VisibilitySM . '-sm';
        }
        if($this->owner->VisibilityMD && $this->owner->VisibilityMD != 'default'){
            $classes .= ' ' . $this->owner->VisibilityMD . '-md';
        }
        if($this->owner->VisibilityLG && $this->owner->VisibilityLG != 'default'){
            $classes .= ' ' . $this->owner->VisibilityLG . '-lg';
        }
        return $classes;
    }

    /**
     * @return string
     */
    public function BulmaColClasses(){
        //Col options
        $classes = '';
        if($this->owner->SizeXS){
            $classes .= ' column is-' . $this->owner->SizeXS . '-mobile';
        }
        if($this->owner->SizeSM){
            $classes .= ' column is-' . $this->owner->SizeSM . '-tablet';
        }
        if($this->owner->SizeMD){
            $classes .= ' column is-' . $this->owner->SizeMD . '-desktop';
        }
        if($this->owner->SizeLG){
            $classes .= ' column is-' . $this->owner->SizeLG . '-widescreen';
        }
        //Offset options
        if($this->owner->OffsetXS){
            $classes .= ' column is-offset-' . $this->owner->OffsetXS . '-mobile';
        }
        if($this->owner->OffsetSM){
            $classes .= ' column is-offset-' . $this->owner->OffsetSM . '-tablet';
        }
        if($this->owner->OffsetMD){
            $classes .= ' column is-offset-' . $this->owner->OffsetMD . '-desktop';
        }
        if($this->owner->OffsetLG){
            $classes .= ' column is-offset-' . $this->owner->OffsetLG . '-widescreen';
        }
        //Visibility options
        if($this->owner->VisibilityXS && $this->owner->VisibilityXS != 'default'){
            $classes .= ' column is-' . $this->owner->VisibilityXS . '-mobile';
        }
        if($this->owner->VisibilitySM && $this->owner->VisibilitySM != 'default'){
            $classes .= ' column is-' . $this->owner->VisibilitySM . '-tablet-only';
        }
        if($this->owner->VisibilityMD && $this->owner->VisibilityMD != 'default'){
            $classes .= ' column is-' . $this->owner->VisibilityMD . '-desktop-only';
        }
        if($this->owner->VisibilityLG && $this->owner->VisibilityLG != 'default'){
            $classes .= ' column is-' . $this->owner->VisibilityLG . '-widescreen-only is-' . $this->owner->VisibilityLG . '-fullhd';
        }
        return $classes;
    }

    public function ColClasses() {
        switch (Config::forClass('TheWebmen\ElementalGrid')->get('cssFramework')){
            case 'bulma':
                return $this->BulmaColClasses();
                break;
            default:
                return $this->BootstrapColClasses();
        }
    }

    public function getBlockType(){
        $type = $this->owner->config()->get('block_type');
        return $type ? $type : 'column';
    }

}
