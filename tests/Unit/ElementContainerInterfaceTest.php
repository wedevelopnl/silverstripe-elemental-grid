<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit;

use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\ContainerType;
use WeDevelop\ElementalGrid\ElementContainerInterface;

#[CoversClass(ElementContainerInterface::class)]
final class ElementContainerInterfaceTest extends TestCase
{
    private \ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new \ReflectionClass(ElementContainerInterface::class);
    }

    public function testIsAnInterface(): void
    {
        $this->assertTrue($this->reflection->isInterface());
    }

    public function testDeclaresExactlyThreeMethods(): void
    {
        $this->assertCount(3, $this->reflection->getMethods());
    }

    #[DataProvider('methodSignatureProvider')]
    public function testMethodHasExpectedSignature(
        string $methodName,
        string $expectedReturnType,
        int $expectedParameterCount,
    ): void {
        $this->assertTrue(
            $this->reflection->hasMethod($methodName),
            sprintf('Interface must declare method %s', $methodName),
        );

        $method = $this->reflection->getMethod($methodName);

        $this->assertSame(
            $expectedParameterCount,
            $method->getNumberOfParameters(),
            sprintf('%s() must accept %d parameters', $methodName, $expectedParameterCount),
        );

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, sprintf('%s() must declare a return type', $methodName));
        $this->assertFalse($returnType->allowsNull(), sprintf('%s() return type must not be nullable', $methodName));
        $this->assertSame(
            $expectedReturnType,
            $returnType->getName(),
            sprintf('%s() must return %s', $methodName, $expectedReturnType),
        );
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function methodSignatureProvider(): iterable
    {
        yield 'getChildArea' => ['getChildArea', ElementalArea::class, 0];
        yield 'hasChildren' => ['hasChildren', 'bool', 0];
        yield 'getContainerType' => ['getContainerType', ContainerType::class, 0];
    }
}
