<?php

namespace TheWebmen\ElementalGrid\CSSFramework;

interface CSSFrameworkInterface
{
    /**
     * @return string
     */
    public function getColumnClasses();

    /**
     * @return array
     */
    public function getVisibilityClasses();

    /**
     * @return string
     */
    public function getTitleSizeClass();

    /***
     * @param bool $fluid
     * @return mixed
     */
    public function getContainerClass($fluid);
}
