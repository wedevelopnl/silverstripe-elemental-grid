<?php

namespace TheWebmen\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

final class BulmaCSSFramework implements CSSFrameworkInterface
{
    /**
     * @var BaseElement
     */
    private $baseElement;

    private const COLUMN_CLASSNAME = 'column';

    private const ROW_CLASSNAME = 'columns is-multiline';

    private const CONTAINER_CLASSNAME = 'container';

    private const FLUID_CONTAINER_CLASSNAME = 'container is-fluid';

    /**
     * @param BaseElement $baseElement
     */
    public function __construct($baseElement)
    {
        $this->baseElement = $baseElement;
    }

    /**
     * @return string
     */
    public function getRowClasses()
    {
        return self::ROW_CLASSNAME;
    }

    /**
     * @return string
     */
    public function getColumnClasses()
    {
        $sizeClasses = $this->getSizeClasses();
        $offsetClasses = $this->getOffsetClasses();
        $visibilityClasses = $this->getVisibilityClasses();

        $classes = array_merge([self::COLUMN_CLASSNAME], $sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    /**
     * @return array
     */
    private function getVisibilityClasses()
    {
        $classes = [];

        if ($this->baseElement->VisibilityXS === 'hidden') {
            $classes[] = 'is-' . $this->baseElement->VisibilityXS . '-mobile';
        }
        if ($this->baseElement->VisibilitySM === 'hidden') {
            $classes[] = 'is-' . $this->baseElement->VisibilitySM . '-tablet-only';
        }
        if ($this->baseElement->VisibilityMD === 'hidden') {
            $classes[] = 'is-' . $this->baseElement->VisibilityMD . '-desktop-only';
        }
        if ($this->baseElement->VisibilityLG === 'hidden') {
            $classes[] = 'is-' . $this->baseElement->VisibilityLG . '-widescreen-only';
        }
        if ($this->baseElement->VisibilityXL === 'hidden') {
            $classes[] = 'is-' . $this->baseElement->VisibilityXL . '-fullhd';
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
            $classes[] = 'is-' . $this->baseElement->SizeXS . '-mobile';
        }
        if ($this->baseElement->SizeSM) {
            $classes[] = 'is-' . $this->baseElement->SizeSM . '-tablet';
        }
        if ($this->baseElement->SizeMD) {
            $classes[] = 'is-' . $this->baseElement->SizeMD . '-desktop';
        }
        if ($this->baseElement->SizeLG) {
            $classes[] = 'is-' . $this->baseElement->SizeLG . '-widescreen';
        }
        if ($this->baseElement->SizeXL) {
            $classes[] = 'is-' . $this->baseElement->SizeXL . '-fullhd';
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
            $classes[] = 'is-offset-' . $this->baseElement->OffsetXS . '-mobile';
        }
        if ($this->baseElement->OffsetSM) {
            $classes[] = 'is-offset-' . $this->baseElement->OffsetSM . '-tablet';
        }
        if ($this->baseElement->OffsetMD) {
            $classes[] = 'is-offset-' . $this->baseElement->OffsetMD . '-desktop';
        }
        if ($this->baseElement->OffsetLG) {
            $classes[] = 'is-offset-' . $this->baseElement->OffsetLG . '-widescreen';
        }
        if ($this->baseElement->OffsetXL) {
            $classes[] = 'is-offset-' . $this->baseElement->OffsetXL . '-fullhd';
        }

        return $classes;
    }

    /**
     * @return string
     */
    public function getTitleSizeClass()
    {
        switch ($this->baseElement->TitleClass) {
            case 'h1':
                return 'title is-size-1';
            case 'h2':
                return 'title is-size-2';
            case 'h3':
                return 'title is-size-3';
            case 'h4':
                return 'title is-size-4';
            case 'h5':
                return 'title is-size-5';
            case 'h6':
                return 'title is-size-6';
            case 'div':
                return 'div';
            default:
                return $this->baseElement->TitleClass;
        }
    }

    /***
     * @param bool $fluid
     * @return string
     */
    public function getContainerClass($fluid)
    {
        if ($fluid) {
            return self::FLUID_CONTAINER_CLASSNAME;
        }

        return self::CONTAINER_CLASSNAME;
    }
}
