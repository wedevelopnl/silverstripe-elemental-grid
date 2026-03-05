<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Contract;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Contract\ContainerType;

#[CoversClass(ContainerType::class)]
final class ContainerTypeTest extends TestCase
{
    public function testIsStringBackedEnum(): void
    {
        $reflection = new \ReflectionEnum(ContainerType::class);

        $this->assertTrue($reflection->isBacked());
        $this->assertSame('string', $reflection->getBackingType()->getName());
    }

    public function testHasExactlyThreeCases(): void
    {
        $this->assertCount(3, ContainerType::cases());
    }

    #[DataProvider('caseValueProvider')]
    public function testCaseHasExpectedValue(ContainerType $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllValuesAreUnique(): void
    {
        $values = array_map(
            static fn (ContainerType $case): string => $case->value,
            ContainerType::cases(),
        );

        $this->assertSame($values, array_unique($values));
    }

    /**
     * @return iterable<string, array{ContainerType, string}>
     */
    public static function caseValueProvider(): iterable
    {
        yield 'Section' => [ContainerType::Section, 'section'];
        yield 'Row' => [ContainerType::Row, 'row'];
        yield 'Column' => [ContainerType::Column, 'column'];
    }
}
