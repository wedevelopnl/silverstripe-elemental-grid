<?php

namespace Webmen\ElementalGrid\CSSFramework;

interface CSSFrameworkInterface
{
    public function getColumnClasses(): string;

    public function getTitleSizeClass(): string;

    public function getFluidContainerClass(): string;
}
