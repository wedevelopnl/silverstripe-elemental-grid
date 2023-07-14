<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

use WeDevelop\ElementalGrid\ElementalConfig;

interface CSSFrameworkInterface
{
    public const DIRECTION_LEFT = 'l';

    public const DIRECTION_TOP = 't';

    public const DIRECTION_BOTTOM = 'b';

    public const DIRECTION_RIGHT = 'r';

    public function getRowClasses(): string;

    public function getColumnClasses(): string;

    public function getTitleSizeClass(): string;

    public function getContainerClass(bool $fluid): string;

    public function getMediaRatioClass(?string $mediaRatio = null): ?string;

    public function getColumnClass(): string;

    public function getViewportName(): string;

    public function getContentPaddingClass(string $direction, int $size): string;

    public function getInitialContentColumnClass(): ?string;

    public function getMediaColumnOrderClasses(string $mediaPosition): string;

    public function getContentColumnOrderClasses(string $mediaPosition): string;

    public function getMediaColumnWidthClass(?string $contentColumnWidth): ?string;

    public function getContentColumnWidthClass(?string $contentColumnWidth): string;
}
