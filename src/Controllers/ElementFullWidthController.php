<?php

namespace TheWebmen\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;


class ElementFullWidthController extends ElementController
{

    private $_needClosing = false;
    private $_after = false;

    public function forTemplate()
    {
        return $this->renderWith([
            'type' => 'Layout',
            'TheWebmen\\ElementalGrid\\ElementFullWidthHolder'
        ]);
    }

    /**
     * @return bool
     */
    public function getNeedClosing()
    {
        return $this->_needClosing;
    }

    /**
     * @param bool $needClosing
     */
    public function setNeedClosing($needClosing)
    {
        $this->_needClosing = $needClosing;
    }

    /**
     * @return bool
     */
    public function getAfter()
    {
        return $this->_after;
    }

    /**
     * @param bool $after
     */
    public function setAfter($after)
    {
        $this->_after = $after;
    }

}
