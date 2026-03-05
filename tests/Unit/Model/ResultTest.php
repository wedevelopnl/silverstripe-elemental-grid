<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Model\Result;
use WeDevelop\Grid\Model\ValidationError;

#[CoversClass(Result::class)]
final class ResultTest extends TestCase
{
    public function testOkIsOk(): void
    {
        $result = Result::ok('hello');

        $this->assertTrue($result->isOk());
        $this->assertFalse($result->isErr());
    }

    public function testFailIsErr(): void
    {
        $result = Result::fail(new ValidationError('bad'));

        $this->assertFalse($result->isOk());
        $this->assertTrue($result->isErr());
    }

    public function testUnwrapReturnsValueOnSuccess(): void
    {
        $result = Result::ok(42);

        $this->assertSame(42, $result->unwrap());
    }

    public function testUnwrapThrowsLogicExceptionOnFailure(): void
    {
        $result = Result::fail(new ValidationError('bad'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot unwrap a failed Result');
        $result->unwrap();
    }

    public function testErrorsReturnsEmptyListOnSuccess(): void
    {
        $result = Result::ok('value');

        $this->assertSame([], $result->errors());
    }

    public function testErrorsReturnsListOnFailure(): void
    {
        $e1 = new ValidationError('first');
        $e2 = new ValidationError('second', 'field_name');
        $result = Result::fail($e1, $e2);

        $this->assertSame([$e1, $e2], $result->errors());
    }

    public function testFailRequiresAtLeastOneError(): void
    {
        $this->expectException(\ArgumentCountError::class);

        /** @phpstan-ignore arguments.count */
        Result::fail();
    }

    public function testMapTransformsSuccessValue(): void
    {
        $result = Result::ok(5);
        $mapped = $result->map(static fn (int $v): int => $v * 2);

        $this->assertTrue($mapped->isOk());
        $this->assertSame(10, $mapped->unwrap());
    }

    public function testMapIsNoOpOnFailure(): void
    {
        $error = new ValidationError('bad');
        $result = Result::fail($error);
        $called = false;
        $mapped = $result->map(static function (mixed $v) use (&$called): string {
            $called = true;

            return 'should not run';
        });

        $this->assertTrue($mapped->isErr());
        $this->assertSame([$error], $mapped->errors());
        $this->assertFalse($called, 'map() must not invoke the callable on a failed Result');
    }

    public function testOkWithNullValue(): void
    {
        $result = Result::ok(null);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->unwrap());
    }
}
