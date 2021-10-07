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

    /***
     * Kan param ook gedefinieerd worden?
     * @param bool $fluid
     * @return mixed
     */
    public function getContainerClass($fluid);
}
