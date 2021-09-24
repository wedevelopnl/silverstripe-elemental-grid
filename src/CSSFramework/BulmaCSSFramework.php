<?php

namespace Webmen\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

final class BulmaCSSFramework implements CSSFrameworkInterface
{
    private BaseElement $baseElement;

    private const COLUMN_CLASSNAME = 'column';

    public const ROW_CLASSNAME = 'columns is-multiline';

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

        $classes = array_merge([self::COLUMN_CLASSNAME], $sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    private function getVisibilityClasses(): array
    {
        $classes = [];

        if ($this->baseElement->VisibilityXS !== 'visible') {
            array_push($classes, 'is-' . $this->baseElement->VisibilityXS . '-mobile');
        }
        if ($this->baseElement->VisibilitySM !== 'visible') {
            array_push($classes, 'is-' . $this->baseElement->VisibilitySM . '-tablet-only');
        }
        if ($this->baseElement->VisibilityMD !== 'visible') {
            array_push($classes, 'is-' . $this->baseElement->VisibilityMD . '-desktop-only');
        }
        if ($this->baseElement->VisibilityLG !== 'visible') {
            array_push($classes, 'is-' . $this->baseElement->VisibilityLG . '-widescreen-only');
        }
        if ($this->baseElement->VisibilityXL !== 'visible') {
            array_push($classes, 'is-' . $this->baseElement->VisibilityXL . '-fullhd-only');
        }

        return $classes;
    }

    private function getSizeClasses(): array
    {
        $classes = [];

        if ($this->baseElement->SizeXS) {
            array_push($classes, 'is-' . $this->baseElement->SizeXS . '-mobile');
        }
        if ($this->baseElement->SizeSM) {
            array_push($classes, 'is-' . $this->baseElement->SizeSM . '-tablet');
        }
        if ($this->baseElement->SizeMD) {
            array_push($classes, 'is-' . $this->baseElement->SizeMD . '-desktop');
        }
        if ($this->baseElement->SizeLG) {
            array_push($classes, 'is-' . $this->baseElement->SizeLG . '-widescreen');
        }
        if ($this->baseElement->SizeXL) {
            array_push($classes, 'is-' . $this->baseElement->SizeXL . '-fullhd');
        }

        return $classes;
    }

    private function getOffsetClasses(): array
    {
        $classes = [];

        if ($this->baseElement->OffsetXS) {
            array_push($classes, 'is-offset-' . $this->baseElement->OffsetXS . '-mobile');
        }
        if ($this->baseElement->OffsetSM) {
            array_push($classes, 'is-offset-' . $this->baseElement->OffsetSM . '-tablet');
        }
        if ($this->baseElement->OffsetMD) {
            array_push($classes, 'is-offset-' . $this->baseElement->OffsetMD . '-desktop');
        }
        if ($this->baseElement->OffsetLG) {
            array_push($classes, 'is-offset-' . $this->baseElement->OffsetLG . '-widescreen');
        }
        if ($this->baseElement->OffsetXL) {
            array_push($classes, 'is-offset-' . $this->baseElement->OffsetXL . '-fullhd');
        }

        return $classes;
    }
}
