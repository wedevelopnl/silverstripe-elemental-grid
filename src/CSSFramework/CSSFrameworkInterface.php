<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

interface CSSFrameworkInterface
{
    public function getRowClasses(): string;

    public function getColumnClasses(): string;

    public function getTitleSizeClass(): string;

    public function getContainerClass(bool $fluid): string;

    public function getMediaRatioClass(?string $mediaRatio = null): ?string;

    public function getColumnClass(): string;

    public function getViewportName(string $viewportName): string;
}
