<?php

namespace WeDevelop\ElementalGrid;

use SilverStripe\Core\Config\Config;
use WeDevelop\ElementalGrid\CSSFramework\BulmaCSSFramework;

final class ElementalConfig
{
    private static bool $enable_custom_title_classes = true;

    private static string $default_viewport = 'MD';

    private static string $default_title_tag = 'h2';

    private static string $css_framework = BulmaCSSFramework::class;

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

    public static function getCSSFrameworkName(): string
    {
        return Config::inst()->get(__CLASS__, 'css_framework');
    }
}
