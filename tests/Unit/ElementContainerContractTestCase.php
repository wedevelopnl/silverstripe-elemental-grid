<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit;

use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\ContainerType;
use WeDevelop\ElementalGrid\ElementContainerInterface;

/**
 * Abstract contract test for ElementContainerInterface implementations.
 *
 * Extend this in each concrete container's test class and implement
 * {@see createContainer()} to return a configured instance.
 */
abstract class ElementContainerContractTestCase extends TestCase
{
    abstract protected function createContainer(): ElementContainerInterface;

    public function testImplementsElementContainerInterface(): void
    {
        $container = $this->createContainer();

        $this->assertInstanceOf(ElementContainerInterface::class, $container);
    }

    public function testGetChildAreaReturnsElementalArea(): void
    {
        $container = $this->createContainer();

        $this->assertInstanceOf(ElementalArea::class, $container->getChildArea());
    }

    public function testGetContainerTypeReturnsValidCase(): void
    {
        $container = $this->createContainer();
        $type = $container->getContainerType();

        $this->assertContains($type, ContainerType::cases());
    }
}
