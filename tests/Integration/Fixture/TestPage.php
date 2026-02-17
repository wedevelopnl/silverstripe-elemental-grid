<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration\Fixture;

use SilverStripe\Dev\TestOnly;

class TestPage extends \Page implements TestOnly
{
    private static string $table_name = 'ElementalGridTestPage';
}
