<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Contract;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Contract\Viewport;

#[CoversClass(Viewport::class)]
final class ViewportTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $viewport = new Viewport('md', 'Medium');

        $this->assertSame('md', $viewport->key);
        $this->assertSame('Medium', $viewport->label);
    }

    #[DataProvider('viewportProvider')]
    public function testVariousAdapterViewports(string $key, string $label): void
    {
        $viewport = new Viewport($key, $label);

        $this->assertSame($key, $viewport->key);
        $this->assertSame($label, $viewport->label);
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

        $this->assertCount(2, $properties);

        foreach ($properties as $property) {
            $this->assertTrue(
                $property->isPublic(),
                sprintf('Property %s should be public', $property->getName()),
            );
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function viewportProvider(): iterable
    {
        yield 'Bootstrap xs' => ['xs', 'Extra Small'];
        yield 'Bootstrap sm' => ['sm', 'Small'];
        yield 'Bootstrap md' => ['md', 'Medium'];
        yield 'Bootstrap lg' => ['lg', 'Large'];
        yield 'Bootstrap xl' => ['xl', 'Extra Large'];
        yield 'Bootstrap xxl' => ['xxl', 'Extra Extra Large'];
        yield 'Tailwind 2xl' => ['2xl', '2X Large'];
        yield 'Bulma desktop' => ['desktop', 'Desktop'];
        yield 'Bulma fullhd' => ['fullhd', 'Full HD'];
    }
}
