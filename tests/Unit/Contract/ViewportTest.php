<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Contract;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Contract\Viewport;

#[CoversClass(Viewport::class)]
final class ViewportTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $viewport = new Viewport('md', 'Medium', 768);

        $this->assertSame('md', $viewport->key);
        $this->assertSame('Medium', $viewport->label);
        $this->assertSame(768, $viewport->minWidth);
    }

    public function testMinWidthAcceptsNull(): void
    {
        $viewport = new Viewport('xs', 'Extra Small', null);

        $this->assertNull($viewport->minWidth);
    }

    #[DataProvider('viewportProvider')]
    public function testVariousAdapterViewports(string $key, string $label, ?int $minWidth): void
    {
        $viewport = new Viewport($key, $label, $minWidth);

        $this->assertSame($key, $viewport->key);
        $this->assertSame($label, $viewport->label);
        $this->assertSame($minWidth, $viewport->minWidth);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(Viewport::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(Viewport::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testAllPropertiesArePublic(): void
    {
        $reflection = new \ReflectionClass(Viewport::class);
        $properties = $reflection->getProperties();

        $this->assertCount(3, $properties);

        foreach ($properties as $property) {
            $this->assertTrue(
                $property->isPublic(),
                sprintf('Property %s should be public', $property->getName()),
            );
        }
    }

    /**
     * @return iterable<string, array{string, string, int|null}>
     */
    public static function viewportProvider(): iterable
    {
        yield 'Bootstrap xs (mobile-first default)' => ['xs', 'Extra Small', null];
        yield 'Bootstrap sm' => ['sm', 'Small', 576];
        yield 'Bootstrap md' => ['md', 'Medium', 768];
        yield 'Bootstrap lg' => ['lg', 'Large', 992];
        yield 'Bootstrap xl' => ['xl', 'Extra Large', 1200];
        yield 'Bootstrap xxl' => ['xxl', 'Extra Extra Large', 1400];
        yield 'Tailwind 2xl' => ['2xl', '2X Large', 1536];
        yield 'Bulma desktop' => ['desktop', 'Desktop', 1024];
        yield 'Bulma fullhd' => ['fullhd', 'Full HD', 1408];
    }
}
