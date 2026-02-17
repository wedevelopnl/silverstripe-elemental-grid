<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Integration;

use SilverStripe\Dev\SapphireTest;

final class ExampleTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testPlaceholder(): void
    {
        $this->assertTrue(true);
    }
}
