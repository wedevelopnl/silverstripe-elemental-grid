<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

final class BulmaCSSFramework implements CSSFrameworkInterface
{
    private BaseElement $baseElement;

    private const COLUMN_CLASSNAME = 'column';

    private const ROW_CLASSNAME = 'columns is-multiline';

    private const CONTAINER_CLASSNAME = 'container';

    private const FLUID_CONTAINER_CLASSNAME = 'container is-fluid';

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

        $classes = array_merge([self::COLUMN_CLASSNAME], $sizeClasses, $offsetClasses, $visibilityClasses);

        return implode(' ', $classes);
    }

    private function getVisibilityClasses(): array
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

    private function getSizeClasses(): array
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

    private function getOffsetClasses(): array
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

    public function getTitleSizeClass(): ?string
    {
        return match ($this->baseElement->TitleClass) {
            'h1' => 'title is-size-1',
            'h2' => 'title is-size-2',
            'h3' => 'title is-size-3',
            'h4' => 'title is-size-4',
            'h5' => 'title is-size-5',
            'h6' => 'title is-size-6',
            default => 'title',
        };
    }

    public function getContainerClass(bool $fluid): string
    {
        return $fluid ? self::FLUID_CONTAINER_CLASSNAME : self::CONTAINER_CLASSNAME;
    }

    public function getMediaRatioClass(string $mediaRatio = null): ?string
    {
        return $mediaRatio ? 'is-' . str_replace('x', 'by', $mediaRatio) : null;
    }

    public function getViewportName(string $viewportName): string
    {
        $viewports = [
            'xl' => 'fullhd',
            'lg' => 'widescreen',
            'md' => 'desktop',
            'sm' => 'tablet',
            'xs' => 'mobile',
        ];

        return $viewports[strtolower($viewportName)];
    }
}
