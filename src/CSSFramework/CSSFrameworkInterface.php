<?php

namespace WeDevelop\ElementalGrid\CSSFramework;

interface CSSFrameworkInterface
{
    public function getColumnClasses(): string;

    public function getTitleSizeClass(): string;

    public function getContainerClass(bool $fluid): string;

    public function getMediaRatioClass(?string $mediaRatio): ?string;

    public function getColumnClass(): string;
}
