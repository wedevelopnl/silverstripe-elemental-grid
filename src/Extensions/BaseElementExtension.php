<?php

namespace TWM\ElementalGrid\Extensions;

use SilverStripe\Core\Config\Config;

class BaseElementExtension extends \SilverStripe\ORM\DataExtension {

    /**
     * @config
     */
    private static $num_columns = 12;

    /**
     * @var array
     */
    private static $db = array(
        'SizeMD' => 'Int'
    );

    /**
     * Get the options for col sizes
     * @return array
     */
    public static function getColSizeOptions(){
        $config = Config::inst()->get(__CLASS__);
        $numColumns = $config['num_columns'];
        $out = array();
        for($i = 1; $i < $numColumns + 1; $i++){
            $out[$i] = _t(__CLASS__ . '.COLUMN', 'Column') . ' ' . $i . '/' . $numColumns;
        }
        return $out;
    }

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(\SilverStripe\Forms\FieldList $fields)
    {
        if( $this->owner->ClassName == 'TWM\ElementalGrid\Models\ElementRow' ) {
            $fields->removeByName('SizeMD');
        } else {
            $fields->findOrMakeTab('Root.Column', _t(__CLASS__ . '.COLUMN', 'Column'));
            $fields->addFieldToTab('Root.Column', \SilverStripe\Forms\DropdownField::create('SizeMD', _t(__CLASS__ . '.SIZE_MD', 'Size'), self::getColSizeOptions()));
        }
        parent::updateCMSFields($fields);
    }

    /**
     * @return string
     */
    public function BootstrapColClasses(){
        return 'col-md-' . $this->owner->SizeMD;
    }

}
