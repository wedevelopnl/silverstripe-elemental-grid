<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Fixture;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Dev\TestOnly;

class MultiAreaTestPage extends \Page implements TestOnly
{
    private static string $table_name = 'ElementalGridMultiAreaTestPage';

    /** @var array<string, class-string> */
    private static array $has_one = [
        'SecondaryArea' => ElementalArea::class,
    ];

    /** @var list<string> */
    private static array $owns = [
        'SecondaryArea',
    ];
}
