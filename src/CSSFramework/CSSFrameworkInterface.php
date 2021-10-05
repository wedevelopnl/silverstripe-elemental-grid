<?php

namespace TheWebmen\ElementalGrid\CSSFramework;

interface CSSFrameworkInterface
{
    /**
     * @return string
     */
    public function getColumnClasses();

    /**
     * @return string
     */
    public function getTitleSizeClass();

    /**
     * @return string
     */
    public function getContainerClass();

    /**
     * @return string
     */
    public function getFluidContainerClass();
}
