<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

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
     * @param bool $fluid
     * @return mixed
     */
    public function getContainerClass($fluid);
}
