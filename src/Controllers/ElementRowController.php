<?php

namespace TheWebmen\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;


class ElementRowController extends ElementController
{

    private $_isLastRow = false;
    private $_isFirstRow = false;
    private $_previousRow = false;

    public function forTemplate()
    {
        return $this->element->forTemplate(false);
    }

    public function getIsLastRow(){
        return $this->_isLastRow;
    }

    public function setIsLastRow($value){
        $this->_isLastRow = $value;
    }

    public function getIsFirstRow(){
        return $this->_isFirstRow;
    }

    public function setIsFirstRow($value){
        $this->_isFirstRow = $value;
    }

    public function getPreviousRow(){
        return $this->_previousRow;
    }

    public function setPreviousRow($value){
        $this->_previousRow = $value;
    }

}
