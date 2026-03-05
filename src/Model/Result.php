<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

/**
 * Generic success/failure container for validation flows.
 *
 * Use Result::ok($value) for success and Result::fail($errors...) for expected failures.
 * Exceptions remain for truly exceptional situations (bugs, infrastructure failures).
 *
 * @template T
 */
final readonly class Result
{
    /**
     * @param T $value
     * @param list<ValidationError> $errors
     */
    private function __construct(
        private bool $ok,
        private mixed $value,
        private array $errors,
    ) {
    }

    /**
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function ok(mixed $value): self
    {
        return new self(ok: true, value: $value, errors: []);
    }

    /**
     * @return self<never>
     */
    public static function fail(ValidationError $first, ValidationError ...$rest): self
    {
        /** @var self<never> Safe: failed Results never expose their value via unwrap() */
        $result = new self(ok: false, value: null, errors: [$first, ...array_values($rest)]);

        return $result;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return !$this->ok;
    }

    /**
     * Returns the success value.
     *
     * @return T
     * @throws \LogicException If called on a failed Result (programmer bug)
     */
    public function unwrap(): mixed
    {
        if (!$this->ok) {
            throw new \LogicException('Cannot unwrap a failed Result');
        }

        return $this->value;
    }

    /** @return list<ValidationError> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Transform the success value. No-op on failure.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        if (!$this->ok) {
            // Reconstruct instead of returning $this so PHPStan can narrow self<T> → self<U>
            /** @var self<U> */
            $result = new self(ok: false, value: null, errors: $this->errors);

            return $result;
        }

        return self::ok($fn($this->value));
    }
}
