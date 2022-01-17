<?php

namespace TheWebmen\ElementalGrid;

use SilverStripe\Core\Config\Config;

final class ElementalConfig
{
    /***
     * @var bool
     */
    private static $enable_custom_title_classes;

    /***
     * @var string
     */
    private static $default_viewport;

    /***
     * @var string
     */
    private static $default_title_tag;

    /***
     * @var string
     */
    private static $css_framework;

    /**
     * @return bool
     */
    public static function getEnableCustomTitleClasses()
    {
        return Config::inst()->get(__CLASS__, 'enable_custom_title_classes');
    }

    /**
     * @return string
     */
    public static function getDefaultViewport()
    {
        return Config::inst()->get(__CLASS__, 'default_viewport');
    }

    /**
     * @return string
     */
    public static function getDefaultTitleTag()
    {
        return Config::inst()->get(__CLASS__, 'default_title_tag');
    }

    /**
     * @return string
     */
    public static function getCSSFrameworkName()
    {
        return Config::inst()->get(__CLASS__, 'css_framework');
    }
}
