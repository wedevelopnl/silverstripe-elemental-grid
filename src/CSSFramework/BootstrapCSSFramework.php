<?php

namespace Webmen\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

final class BootstrapCSSFramework implements CSSFrameworkInterface
{
    private BaseElement $baseElement;

    private const COLUMN_CLASSNAME = 'col';

    private const ROW_CLASSNAME = 'row';

    public function __construct(BaseElement $baseElement)
    {
        $this->baseElement = $baseElement;
    }

    public function getRowClasses(): string
    {
        return self::ROW_CLASSNAME;
    }

    public function getColumnClasses(): string
    {
        $sizeClasses = $this->getSizeClasses();
        $offsetClasses = $this->getOffsetClasses();
        $visibilityClasses = $this->getVisibilityClasses();

        $classes = array_merge($sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    private function getVisibilityClasses(): array
    {
        $classes = [];

        if ($this->baseElement->VisibilityXS !== 'visible') {
            array_push($classes, 'd-none d-sm-block');
        }
        if ($this->baseElement->VisibilitySM !== 'visible') {
            array_push($classes, 'd-sm-none d-md-block');
        }
        if ($this->baseElement->VisibilityMD !== 'visible') {
            array_push($classes, 'd-md-none d-lg-block');
        }
        if ($this->baseElement->VisibilityLG !== 'visible') {
            array_push($classes, 'd-lg-none d-xl-block');
        }
        if ($this->baseElement->VisibilityXL !== 'visible') {
            array_push($classes, 'd-xl-none');
        }

        return $classes;
    }

    private function getSizeClasses(): array
    {
        $classes = [];

        if ($this->baseElement->SizeXS) {
            array_push($classes, 'xs-' . $this->baseElement->SizeXS);
        }
        if ($this->baseElement->SizeSM) {
            array_push($classes, 'sm-' . $this->baseElement->SizeSM);
        }
        if ($this->baseElement->SizeMD) {
            array_push($classes, 'md-' . $this->baseElement->SizeMD);
        }
        if ($this->baseElement->SizeLG) {
            array_push($classes, 'lg-' . $this->baseElement->SizeLG);
        }
        if ($this->baseElement->SizeXL) {
            array_push($classes, 'xl-' . $this->baseElement->SizeXL);
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
            array_push($classes, 'offset-xs-' . $this->baseElement->OffsetXS);
        }
        if ($this->baseElement->OffsetSM) {
            array_push($classes, 'offset-sm-' . $this->baseElement->OffsetSM);
        }
        if ($this->baseElement->OffsetMD) {
            array_push($classes, 'offset-md-' . $this->baseElement->OffsetMD);
        }
        if ($this->baseElement->OffsetLG) {
            array_push($classes, 'offset-lg-' . $this->baseElement->OffsetLG);
        }
        if ($this->baseElement->OffsetXL) {
            array_push($classes, 'offset-xl-' . $this->baseElement->OffsetXL);
        }

        return $classes;
    }

    public function getTitleSizeClass(): string
    {
        return $this->baseElement->TitleSize;
    }
}
