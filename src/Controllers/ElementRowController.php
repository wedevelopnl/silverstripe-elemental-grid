<?php

namespace WeDevelop\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;
use DNADesign\Elemental\Models\BaseElement;

/**
 * Class ElementRowController
 * @package WeDevelop\ElementalGrid\Controllers
 */
class ElementRowController extends ElementController
{
    /**
     * @var bool
     */
    private $isLastRow = false;

    /**
     * @var bool
     */
    private $isFirstRow = false;

    /**
     * @var BaseElement
     */
    private $previousRow;

    /**
     * @return string
     */
    public function forTemplate()
    {
        return $this->element->forTemplate(false);
    }

    /**
     * @return bool
     */
    public function getIsLastRow()
    {
        return $this->isLastRow;
    }

    /**
     * @param bool $value
     */
    public function setIsLastRow($value)
    {
        $this->isLastRow = $value;
    }

    /**
     * @return bool
     */
    public function getIsFirstRow()
    {
        return $this->isFirstRow;
    }

    /**
     * @param bool $value
     */
    public function setIsFirstRow($value)
    {
        $this->isFirstRow = $value;
    }

    /**
     * @return BaseElement
     */
    public function getPreviousRow()
    {
        return $this->previousRow;
    }

    /**
     * @param BaseElement $value
     */
    public function setPreviousRow($value)
    {
        $this->previousRow = $value;
    }
}
