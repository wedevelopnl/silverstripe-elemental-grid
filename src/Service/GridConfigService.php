<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\GridConfigServiceInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;
use WeDevelop\ElementalGrid\Exception\InvalidGridValueException;

/**
 * Central grid configuration service that wraps the active adapter
 * and applies site-level YAML overrides.
 *
 * YAML config properties:
 * - `total_columns` (int|null): Override the adapter's column count. Null defers to the adapter.
 * - `default_viewport` (string|null): Override the default viewport by key. Null defers to the adapter.
 */
class GridConfigService implements GridConfigServiceInterface
{
    use Configurable;
    use Extensible;
    use Injectable;

    /** @config */
    private static ?int $total_columns = null;

    /** @config */
    private static ?string $default_viewport = null;

    public function __construct(
        private readonly GridAdapterInterface $adapter,
    ) {
    }

    /** @return list<Viewport> */
    public function getViewports(): array
    {
        return $this->adapter->getViewports();
    }

    /** @return positive-int */
    public function getColumnCount(): int
    {
        /** @var int|null $override */
        $override = static::config()->get('total_columns');

        if ($override === null) {
            return $this->adapter->getColumnCount();
        }

        if ($override <= 0) {
            throw InvalidGridValueException::forColumnCount($override);
        }

        return $override;
    }

    public function getDefaultViewport(): Viewport
    {
        /** @var string|null $override */
        $override = static::config()->get('default_viewport');

        if ($override === null) {
            return $this->adapter->getDefaultViewport();
        }

        return $this->resolveViewportByKey($override);
    }

    public function getWidthClass(string $viewport, int $width): string
    {
        return $this->adapter->getWidthClass($viewport, $width);
    }

    public function getOffsetClass(string $viewport, int $offset): string
    {
        return $this->adapter->getOffsetClass($viewport, $offset);
    }

    /** @return list<string> */
    public function getVisibilityClasses(string $viewport): array
    {
        return $this->adapter->getVisibilityClasses($viewport);
    }

    public function getRowClasses(): string
    {
        return $this->adapter->getRowClasses();
    }

    public function getContainerClass(bool $fluid): string
    {
        return $this->adapter->getContainerClass($fluid);
    }

    /** @return array<string, string> */
    public function getTitleClassOptions(): array
    {
        return $this->adapter->getTitleClassOptions();
    }

    public function getCssPath(): ?string
    {
        return $this->adapter->getCssPath();
    }

    /**
     * Resolve a viewport key string to its Viewport value object.
     *
     * @throws InvalidGridValueException If the key does not match any adapter viewport
     */
    private function resolveViewportByKey(string $key): Viewport
    {
        foreach ($this->adapter->getViewports() as $viewport) {
            if ($viewport->key === $key) {
                return $viewport;
            }
        }

        throw InvalidGridValueException::forViewport($key);
    }
}
