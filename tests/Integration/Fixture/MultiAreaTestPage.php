<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Fixture;

use SilverStripe\Dev\TestOnly;
use WeDevelop\Grid\Elements\Section;

class MultiAreaTestPage extends \Page implements TestOnly
{
    private static string $table_name = 'GridMultiAreaTestPage';

    // /** @var array<string, class-string> */
    // private static array $has_many = [
    //     'SecondarySections' => Section::class . '.Parent',
    // ];

    // $owns removed to prevent OOM during schema resolution
    // /** @var list<string> */
    // private static array $owns = [
    //     'SecondarySections',
    // ];
}
