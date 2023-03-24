<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

/**
 * CSS Framework class for Bootstrap (5). Cause bootstrap did not change grid
 * classes, Bootstrap 4 should work too.
 *
 * https://getbootstrap.com/docs/5.3/layout/grid/#grid-options
 * https://getbootstrap.com/docs/4.6/layout/grid/#grid-options
 */
final class BootstrapCSSFramework implements CSSFrameworkInterface
{
    /**
     * @var BaseElement
     */
    private $baseElement;

    private const COLUMN_CLASSNAME = 'col';

    private const ROW_CLASSNAME = 'row';

    private const CONTAINER_CLASSNAME = 'container';

    private const FLUID_CONTAINER_CLASSNAME = 'container-fluid';

    /**
     * Initialize CSS framework for elemental row element.
     *
     * @param BaseElement $baseElement Elemental row element
     */
    public function __construct($baseElement)
    {
        $this->baseElement = $baseElement;
    }

    /**
     * Return bootstrap row class name for grid layouts.
     *
     * @return string
     */
    public function getRowClasses()
    {
        return self::ROW_CLASSNAME;
    }

    /**
     * Return bootstrap column classes for grid layout. It return classes for
     * size, offset and visibility for different screen sizes based on the
     * elemental settings.
     *
     * @return string
     */
    public function getColumnClasses()
    {
        $sizeClasses = $this->getSizeClasses();
        $offsetClasses = $this->getOffsetClasses();
        $visibilityClasses = $this->getVisibilityClasses();

        $classes = array_merge($sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    /**
     * Get title class from base element.
     *
     * @return string
     */
    public function getTitleSizeClass()
    {
        return $this->baseElement->TitleClass;
    }

    /**
     * Return container class name for fluid or normal design.
     *
     * @param bool $fluid Fluid or normal design (true/false)
     *
     * @return string
     */
    public function getContainerClass($fluid)
    {
        if ($fluid) {
            return self::FLUID_CONTAINER_CLASSNAME;
        }

        return self::CONTAINER_CLASSNAME;
    }

    /**
     * @return array
     */
    private function getVisibilityClasses()
    {
        $classes = [];

        if ($this->baseElement->VisibilityXS === 'hidden') {
            $classes[] = 'd-none d-sm-block';
        }
        if ($this->baseElement->VisibilitySM === 'hidden') {
            $classes[] = 'd-sm-none d-md-block';
        }
        if ($this->baseElement->VisibilityMD === 'hidden') {
            $classes[] = 'd-md-none d-lg-block';
        }
        if ($this->baseElement->VisibilityLG === 'hidden') {
            $classes[] = 'd-lg-none d-xl-block';
        }
        if ($this->baseElement->VisibilityXL === 'hidden') {
            $classes[] = 'd-xl-none';
        }

        return $classes;
    }

    /**
     * @return array
     */
    private function getSizeClasses()
    {
        $classes = [];

        if ($this->baseElement->SizeXS) {
            $classes[] = $this->baseElement->SizeXS;
        }
        if ($this->baseElement->SizeSM) {
            $classes[] = 'sm-' . $this->baseElement->SizeSM;
        }
        if ($this->baseElement->SizeMD) {
            $classes[] = 'md-' . $this->baseElement->SizeMD;
        }
        if ($this->baseElement->SizeLG) {
            $classes[] = 'lg-' . $this->baseElement->SizeLG;
        }
        if ($this->baseElement->SizeXL) {
            $classes[] = 'xl-' . $this->baseElement->SizeXL;
        }

        foreach ($classes as &$class) {
            $class = sprintf('%s-%s', self::COLUMN_CLASSNAME, $class);
        }

        return $classes;
    }

    /**
     * @return array
     */
    private function getOffsetClasses()
    {
        $classes = [];

        if ($this->baseElement->OffsetXS) {
            $classes[] = 'offset-' . $this->baseElement->OffsetXS;
        }
        if ($this->baseElement->OffsetSM) {
            $classes[] = 'offset-sm-' . $this->baseElement->OffsetSM;
        }
        if ($this->baseElement->OffsetMD) {
            $classes[] = 'offset-md-' . $this->baseElement->OffsetMD;
        }
        if ($this->baseElement->OffsetLG) {
            $classes[] = 'offset-lg-' . $this->baseElement->OffsetLG;
        }
        if ($this->baseElement->OffsetXL) {
            $classes[] = 'offset-xl-' . $this->baseElement->OffsetXL;
        }

        return $classes;
    }
}
