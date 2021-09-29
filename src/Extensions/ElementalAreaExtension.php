<?php

namespace TheWebmen\ElementalGrid\Extensions;

use App\Services\SpotlerService;
use App\Spotler\Exceptions\SpotlerException;
use DNADesign\Elemental\Models\ElementalArea;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use TheWebmen\ElementalGrid\CSSFramework\BootstrapCSSFramework;
use TheWebmen\ElementalGrid\CSSFramework\BulmaCSSFramework;
use TheWebmen\ElementalGrid\Models\ElementRow;

/***
 * Class ElementalAreaExtension
 * @package TheWebmen\ElementalGrid\Extensions
 *
 * @property ElementalArea $owner
 */
class ElementalAreaExtension extends DataExtension
{
    /**
     * @var ArrayList
     */
    private $controllers;

    /**
     * @param object $owner
     */
    public function setOwner($owner)
    {
        parent::setOwner($owner);

        try {
            $this->controllers = $this->owner->ElementControllers();
        } catch (\Exception $exception) {
            $logger = Injector::inst()->get(LoggerInterface::class);
            $logger->error($exception->getMessage(), [
                'exception' => $exception,
            ]);
            $this->controllers = new ArrayList();
        }
    }

    /**
     * @return false|ArrayList
     * @throws \Exception
     */
    public function ElementControllersWithRows()
    {
        if (!$this->controllers->count()) {
            return false;
        }

        //Check for first row
        $first = $this->controllers->first();
        if ($first->ClassName == ElementRow::class) {
            $first->setIsFirstRow(true);
        } else {
            $createdFirstElement = ElementRow::create();
            $createdFirst = $createdFirstElement->getController();
            $createdFirst->setIsFirstRow(true);
            $this->controllers->unshift($createdFirst);
        }

        // Check for last row
        $last = $this->controllers->last();

        if ($last->ClassName == ElementRow::class) {
            $last->setIsLastRow(true);
        } else {
            $createdLastElement = ElementRow::create();
            $createdLast = $createdLastElement->getController();
            $createdLast->setIsLastRow(true);
            $this->controllers->push($createdLast);
        }

        $previousRow = false;
        foreach ($this->controllers as $key => $controller) {
            if($controller) {
                if ($controller->ClassName == ElementRow::class) {
                    if ($previousRow) {
                        $controller->setPreviousRow($previousRow->getElement());
                    }
                    $previousRow = $controller;
                }
            }
        }

        return $this->controllers;
    }
}
