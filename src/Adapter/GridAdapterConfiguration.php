<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Adapter;

use SilverStripe\Core\Config\Configurable;
use WeDevelop\ElementalGrid\Contract\Viewport;
use WeDevelop\ElementalGrid\Exception\InvalidGridValueException;

/**
 * Shared configuration support for grid adapters.
 *
 * Adds SilverStripe's Configurable trait and provides helper methods
 * for filtering viewports, overriding column count, and resolving the
 * default viewport from YAML configuration.
 *
 * YAML config properties (set on the concrete adapter class):
 * - `enabled_viewports` (list<string>|null): Restrict viewports. Null keeps all.
 * - `total_columns` (int|null): Override column count. Null defers to adapter.
 * - `default_viewport` (string|null): Override default viewport key. Null defers to adapter.
 */
trait GridAdapterConfiguration
{
    use Configurable;

    /**
     * @config
     * @var list<string>|null
     */
    private static ?array $enabled_viewports = null;

    /** @config */
    private static ?int $total_columns = null;

    /** @config */
    private static ?string $default_viewport = null;

    /**
     * Filter the full viewport map to only enabled viewports.
     *
     * @param array<string, Viewport> $allViewports Full viewport definitions keyed by viewport key
     * @return array<string, Viewport> Filtered viewport map (preserves definition order)
     * @throws InvalidGridValueException If enabled_viewports is empty or contains unknown keys
     */
    protected function applyViewportFilter(array $allViewports): array
    {
        /** @var list<string>|null $enabled */
        $enabled = static::config()->get('enabled_viewports');

        if ($enabled === null) {
            return $allViewports;
        }

        if ($enabled === []) {
            throw InvalidGridValueException::forEmptyViewports();
        }

        $filtered = [];
        foreach ($enabled as $key) {
            if (!isset($allViewports[$key])) {
                throw InvalidGridValueException::forViewport($key);
            }
            $filtered[$key] = $allViewports[$key];
        }

        return $filtered;
    }

    /**
     * Resolve the effective column count, applying any YAML override.
     *
     * @param positive-int $adapterDefault The adapter's built-in column count
     * @return positive-int
     * @throws InvalidGridValueException If the override is zero or negative
     */
    protected function resolveColumnCount(int $adapterDefault): int
    {
        /** @var int|null $override */
        $override = static::config()->get('total_columns');

        if ($override === null) {
            return $adapterDefault;
        }

        if ($override <= 0) {
            throw InvalidGridValueException::forColumnCount($override);
        }

        /** @var positive-int $override */
        return $override;
    }

    /**
     * Resolve the effective default viewport, applying any YAML override.
     *
     * @param string $adapterDefaultKey The adapter's built-in default viewport key
     * @param array<string, Viewport> $viewports Active (possibly filtered) viewport map
     * @throws InvalidGridValueException If the resolved key is not in the active viewport map
     */
    protected function resolveDefaultViewport(string $adapterDefaultKey, array $viewports): Viewport
    {
        /** @var string|null $override */
        $override = static::config()->get('default_viewport');
        $key = $override ?? $adapterDefaultKey;

        if (!isset($viewports[$key])) {
            throw InvalidGridValueException::forViewport($key);
        }

        return $viewports[$key];
    }
}
