<?php

namespace TheWebmen\ElementalGrid\Extensions;

use SilverStripe\ORM\DataExtension;
use TheWebmen\ElementalGrid\Controllers\ElementFullWidthController;
use TheWebmen\ElementalGrid\Controllers\ElementRowController;
use TheWebmen\ElementalGrid\Models\ElementRow;

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
        $previousKey = false;
        $previousController = false;
        foreach($controllers as $key => $controller){
            if($controller->ClassName == ElementRow::class){
                if($previousRow){
                    $controller->setPreviousRow($previousRow->getElement());
                }
                $previousRow = $controller;
            }else if(get_class($controller) == ElementFullWidthController::class){
                if($previousController && $previousController->ClassName == ElementRow::class){
                    unset($controllers[$previousKey]);
                }
                if($key > 1){
                    $controller->setNeedClosing(true);
                }
                $next = array_key_exists($key + 1, $controllers->toArray()) ? $controllers->toArray()[$key + 1] : false;
                if($next && $next->ClassName != ElementRow::class){
                    $controller->setAfter(ElementRow::create());
                }
            }

            $previousKey = $key;
            $previousController = $controller;
        }

        return $controllers;
    }

}
