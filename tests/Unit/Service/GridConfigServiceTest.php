<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Adapter\BootstrapAdapter;
use WeDevelop\ElementalGrid\Contract\GridConfigServiceInterface;
use WeDevelop\ElementalGrid\Service\GridConfigService;

#[CoversClass(GridConfigService::class)]
final class GridConfigServiceTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $service = new GridConfigService(new BootstrapAdapter());

        $this->assertInstanceOf(GridConfigServiceInterface::class, $service);
    }
}
