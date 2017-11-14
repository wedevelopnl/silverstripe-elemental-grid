<?php

namespace TWM\ElementalGrid\Extensions;

use SilverStripe\ORM\DataExtension;
use TWM\ElementalGrid\Controllers\ElementRowController;
use TWM\ElementalGrid\Models\ElementRow;

class ElementalAreaExtension extends DataExtension {

    public function ElementControllersWithRows(){
        $controllers = $this->owner->ElementControllers();
        if(!$controllers->count()){
            return false;
        }

        //Check for first row
        $first = $controllers->first();
        if($first->ClassName == ElementRow::class){
            $first->setIsFirstRow(true);
        }else{
            $createdFirstElement = ElementRow::create();
            $createdFirst = $createdFirstElement->getController();
            $createdFirst->setIsFirstRow(true);
            $controllers->unshift($createdFirst);
        }

        //Check for last row
        $last = $controllers->last();
        if($last->ClassName == ElementRow::class){
            $last->setIsLastRow(true);
        }else{
            $createdLastElement = ElementRow::create();
            $createdLast = $createdLastElement->getController();
            $createdLast->setIsLastRow(true);
            $controllers->push($createdLast);
        }

        $previousRow = false;
        foreach($controllers as $controller){
            if($controller->ClassName == ElementRow::class){
                if($previousRow){
                    $controller->setPreviousRow($previousRow->getElement());
                }
                $previousRow = $controller;
            }
        }

        return $controllers;
    }

}
