<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;
use WeDevelop\ElementalGrid\ElementalConfig;

final class TailwindCSSFramework implements CSSFrameworkInterface
{
    public static string $framework_key = 'tailwind';

    private BaseElement $baseElement;

    private const COLUMN_CLASSNAME = 'col-span';

    private const OFFSET_CLASSNAME = 'col-start';

    private const ROW_CLASSNAME = 'grid grid-cols-12';

    private const CONTAINER_CLASSNAME = 'container';

    private const FLUID_CONTAINER_CLASSNAME = '';

    public function __construct(BaseElement $baseElement)
    {
        $this->baseElement = $baseElement;
    }

    public function getRowClasses(): string
    {
        $classes[] = self::ROW_CLASSNAME;

        if ($this->getVisibilityClasses()) {
            $classes[] = implode(' ', $this->getVisibilityClasses());
        }

        return implode(' ', $classes);
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
        return $this->baseElement->TitleClass ?? strtolower($this->baseElement->TitleTag);
    }

    public function getContainerClass(bool $fluid): string
    {
        return $fluid ? self::FLUID_CONTAINER_CLASSNAME : self::CONTAINER_CLASSNAME;
    }

    public function getMediaRatioClass(?string $mediaRatio = null): ?string
    {
        if ($mediaRatio === '1x1') {
            return 'relative aspect-square';
        } else if ($mediaRatio === '4x3') {
            return 'relative aspect-4x3';
        } else if ($mediaRatio === '16x9') {
            return 'relative aspect-video';
        }

        return null;
    }

    public function getViewportName(): string
    {
        return strtolower(ElementalConfig::getDefaultViewport());
    }

    public function getContentPaddingClass(string $direction, int $size): string
    {
        $direction = match($direction) {
            self::DIRECTION_TOP => 't',
            self::DIRECTION_RIGHT => 'e',
            self::DIRECTION_BOTTOM => 'b',
            self::DIRECTION_LEFT => 's',
        };

        return sprintf('%s:p%s-%u', $this->getViewportName(), $direction, $size);
    }

    public function getInitialContentColumnClass(): ?string
    {
        return null;
    }

    public function getMediaColumnOrderClasses(string $mediaPosition): string
    {
        return match($mediaPosition) {
            'order-1' => 'order-1',
            'order-2' => 'order-2',
            default => sprintf('order-1 %s:order-2', $this->getViewportName()),
        };
    }

    public function getContentColumnOrderClasses(string $mediaPosition): string
    {
        return match($mediaPosition) {
            'order-1' => 'order-2',
            'order-2' => 'order-1',
            default => sprintf('order-2 %s:order-1', $this->getViewportName()),
        };
    }

    public function getMediaColumnWidthClass(?string $contentColumnWidth): ?string
    {
        if ($contentColumnWidth) {
            return sprintf('%s-12 %s:%s-%s', self::COLUMN_CLASSNAME, $this->getViewportName(), self::COLUMN_CLASSNAME, (ElementalConfig::getGridColumnCount() - $contentColumnWidth));
        }

        return sprintf('%s-12', self::COLUMN_CLASSNAME);
    }

    public function getContentColumnWidthClass(?string $contentColumnWidth): string
    {
        if ($contentColumnWidth) {
            return sprintf('%s-12 %s:%s-%s', self::COLUMN_CLASSNAME, $this->getViewportName(), self::COLUMN_CLASSNAME, $contentColumnWidth);
        }

        return sprintf('%s-12', self::COLUMN_CLASSNAME, $this->getViewportName(), self::COLUMN_CLASSNAME);
    }

    private function getVisibilityClasses(): array
    {
        $classes = [];

        if ($this->baseElement->VisibilityXS === 'hidden') {
            $classes[] = 'hidden sm:block';
        }

        if ($this->baseElement->VisibilitySM === 'hidden') {
            $classes[] = 'sm:hidden md:block';
        }

        if ($this->baseElement->VisibilityMD === 'hidden') {
            $classes[] = 'md:hidden lg:block';
        }

        if ($this->baseElement->VisibilityLG === 'hidden') {
            $classes[] = 'lg:hidden xl:block';
        }

        if ($this->baseElement->VisibilityXL === 'hidden') {
            $classes[] = 'xl:hidden';
        }

        return $classes;
    }

    private function getSizeClasses(): array
    {
        $classes = [];

        if ($this->baseElement->SizeXS) {
            $classes[] = sprintf('%s-%s', self::COLUMN_CLASSNAME, $this->baseElement->SizeXS);
        } else {
            $classes[] = sprintf('%s-12', self::COLUMN_CLASSNAME);
        }

        if ($this->baseElement->SizeSM) {
            $classes[] = sprintf('sm:%s-%s', self::COLUMN_CLASSNAME, $this->baseElement->SizeSM);
        }
        if ($this->baseElement->SizeMD) {
            $classes[] = sprintf('md:%s-%s', self::COLUMN_CLASSNAME, $this->baseElement->SizeMD);
        }
        if ($this->baseElement->SizeLG) {
            $classes[] = sprintf('lg:%s-%s', self::COLUMN_CLASSNAME, $this->baseElement->SizeLG);
        }
        if ($this->baseElement->SizeXL) {
            $classes[] = sprintf('xl:%s-%s', self::COLUMN_CLASSNAME, $this->baseElement->SizeXL);
        }

//        foreach ($classes as &$class) {
//            $class = sprintf('%s-%s', self::COLUMN_CLASSNAME, $class);
//        }

        return $classes;
    }

    private function getOffsetClasses(): array
    {
        $classes = [];

        if ($this->baseElement->OffsetXS) {
            $classes[] = sprintf('%s-%s', self::OFFSET_CLASSNAME, $this->baseElement->OffsetXS + 1);
        }
        if ($this->baseElement->OffsetSM) {
            $classes[] = sprintf('sm:%s-%s', self::OFFSET_CLASSNAME, $this->baseElement->OffsetSM + 1);
        }
        if ($this->baseElement->OffsetMD) {
            $classes[] = sprintf('md:%s-%s', self::OFFSET_CLASSNAME, $this->baseElement->OffsetMD + 1);
        }
        if ($this->baseElement->OffsetLG) {
            $classes[] = sprintf('lg:%s-%s', self::OFFSET_CLASSNAME, $this->baseElement->OffsetLG + 1);
        }
        if ($this->baseElement->OffsetXL) {
            $classes[] = sprintf('xl:%s-%s', self::OFFSET_CLASSNAME, $this->baseElement->OffsetXL + 1);
        }

        return $classes;
    }

}
