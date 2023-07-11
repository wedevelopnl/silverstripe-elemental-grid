<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

final class BootstrapCSSFramework implements CSSFrameworkInterface
{
    private BaseElement $baseElement;

    private const COLUMN_CLASSNAME = 'col';

    private const ROW_CLASSNAME = 'row';

    private const CONTAINER_CLASSNAME = 'container';

    private const FLUID_CONTAINER_CLASSNAME = 'container-fluid';

    public function __construct(BaseElement $baseElement)
    {
        $this->baseElement = $baseElement;
    }

    public function getRowClasses(): string
    {
        return self::ROW_CLASSNAME;
    }

    public function getColumnClass(): string
    {
        return self::COLUMN_CLASSNAME;
    }

    public function getColumnClasses(): string
    {
        $sizeClasses = $this->getSizeClasses();
        $offsetClasses = $this->getOffsetClasses();
        $visibilityClasses = $this->getVisibilityClasses();

        $classes = array_merge($sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    public function getTitleSizeClass(): string
    {
        return $this->baseElement->TitleClass;
    }

    public function getContainerClass(bool $fluid): string
    {
        return $fluid ? self::FLUID_CONTAINER_CLASSNAME : self::CONTAINER_CLASSNAME;
    }

    private function getVisibilityClasses(): array
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

    private function getSizeClasses(): array
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

    private function getOffsetClasses(): array
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

    public function getMediaRatioClass(string $mediaRatio = null): ?string
    {
        return $mediaRatio;
    }

    public function getViewportName(string $viewportName): string
    {
        return strtolower($viewportName);
    }
}
