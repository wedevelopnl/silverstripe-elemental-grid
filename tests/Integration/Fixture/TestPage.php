<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Integration\Fixture;

use SilverStripe\Dev\TestOnly;

class TestPage extends \Page implements TestOnly
{
    private static string $table_name = 'GridTestPage';
}
