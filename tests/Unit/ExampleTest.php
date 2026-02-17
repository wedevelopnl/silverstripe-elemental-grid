<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Module;

#[CoversClass(Module::class)]
final class ExampleTest extends TestCase
{
    public function testModuleName(): void
    {
        $this->assertSame('wedevelopnl/silverstripe-elemental-grid', Module::name());
    }
}
