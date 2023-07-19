<?php

namespace WeDevelop\ElementalGrid;

use SilverStripe\Core\Config\Config;

final class ElementalConfig
{
    private static bool $enable_custom_title_classes = true;

    private static string $default_viewport = 'MD';

    private static string $default_title_tag = 'h2';

    private static string $css_framework = 'bootstrap';

    private static int $grid_column_count = 12;

    public static function getEnableCustomTitleClasses(): bool
    {
        return Config::inst()->get(__CLASS__, 'enable_custom_title_classes');
    }

    public static function getDefaultViewport(): string
    {
        return Config::inst()->get(__CLASS__, 'default_viewport');
    }

    public static function getDefaultTitleTag(): string
    {
        return Config::inst()->get(__CLASS__, 'default_title_tag');
    }

    public static function getGridColumnCount(): int
    {
        return Config::inst()->get(__CLASS__, 'grid_column_count');
    }

    public static function getCSSFrameworkName(): string
    {
        return Config::inst()->get(__CLASS__, 'css_framework');
    }
}
