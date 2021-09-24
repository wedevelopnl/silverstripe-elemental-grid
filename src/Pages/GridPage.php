<?php

namespace Webmen\ElementalGrid\Pages;

class GridPage extends \Page
{
    private static string $icon_class = 'font-icon-thumbnails';

    public function provideI18nEntities(): array
    {
        return [
            'GridPage.SINGULARNAME' => _t(__CLASS__ . '.GRID_PAGE', 'Grid page'),
            'MyObject.PLURALNAME' => _t(__CLASS__ . '.GRID_PAGES', 'Grid pages'),
            'MyObject.PLURALS' => [
                'one' => 'a grid page',
                'other' => '{count} grid pages',
            ],
        ];
    }
}
