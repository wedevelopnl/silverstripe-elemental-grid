<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Adapter;

use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

/**
 * Grid adapter for the Bulma CSS framework (Flexbox column system).
 *
 * Implements Bulma's responsive column classes using its five default
 * breakpoints: mobile, tablet (769px), desktop (1024px), widescreen
 * (1216px), and fullhd (1408px).
 *
 * Bulma treats mobile as the unsuffixed default — width classes use
 * `is-{n}` instead of `is-{n}-mobile`, and offset classes follow the
 * same pattern. All other viewports append `-{viewport}` as a suffix.
 */
final class BulmaAdapter implements GridAdapterInterface
{
    use GridAdapterConfiguration;

    private const int DEFAULT_COLUMNS = 12;
    private const string DEFAULT_VIEWPORT_KEY = 'desktop';

    /** @var array<string, Viewport> */
    private array $viewports;

    /**
     * Visibility class pairs keyed by viewport.
     *
     * All non-last viewports use upward-scoped `is-hidden-{vp}` plus a restore
     * class `is-block-{next}` at the next enabled viewport. The last enabled
     * viewport uses `is-hidden-{vp}` alone.
     *
     * @var array<string, list<string>>
     */
    private array $visibilityMap;

    /** @var positive-int */
    private int $columnCount;

    private Viewport $defaultViewport;

    public function __construct()
    {
        $allViewports = [
            'mobile' => new Viewport('mobile', 'Mobile'),
            'tablet' => new Viewport('tablet', 'Tablet'),
            'desktop' => new Viewport('desktop', 'Desktop'),
            'widescreen' => new Viewport('widescreen', 'Widescreen'),
            'fullhd' => new Viewport('fullhd', 'Full HD'),
        ];

        $this->viewports = $this->applyViewportFilter($allViewports);
        $this->visibilityMap = $this->buildVisibilityMap();
        $this->columnCount = $this->resolveColumnCount(self::DEFAULT_COLUMNS);
        $this->defaultViewport = $this->resolveDefaultViewport(self::DEFAULT_VIEWPORT_KEY, $this->viewports);
    }

    /** @return list<Viewport> */
    public function getViewports(): array
    {
        return array_values($this->viewports);
    }

    /** @return positive-int */
    public function getColumnCount(): int
    {
        return $this->columnCount;
    }

    public function getDefaultViewport(): Viewport
    {
        return $this->defaultViewport;
    }

    public function getWidthClass(string $viewport, int $width): string
    {
        if ($viewport === 'mobile') {
            return sprintf('is-%d', $width);
        }

        return sprintf('is-%d-%s', $width, $viewport);
    }

    public function getOffsetClass(string $viewport, int $offset): string
    {
        if ($viewport === 'mobile') {
            return sprintf('is-offset-%d', $offset);
        }

        return sprintf('is-offset-%d-%s', $offset, $viewport);
    }

    /** @return list<string> */
    public function getVisibilityClasses(string $viewport): array
    {
        return $this->visibilityMap[$viewport];
    }

    public function getRowClasses(): string
    {
        return 'columns is-multiline';
    }

    public function getContainerClass(bool $fluid): string
    {
        if ($fluid) {
            return 'container is-fluid';
        }

        return 'container';
    }

    /** @return array<string, string> */
    public function getTitleClassOptions(): array
    {
        return [
            'is-1' => 'Title 1',
            'is-2' => 'Title 2',
            'is-3' => 'Title 3',
            'is-4' => 'Title 4',
            'is-5' => 'Title 5',
            'is-6' => 'Title 6',
        ];
    }

    public function getBaseWidthClass(int $width): string
    {
        return $this->getWidthClass('mobile', $width);
    }

    public function getBaseOffsetClass(int $offset): string
    {
        return $this->getOffsetClass('mobile', $offset);
    }

    public function getCssPath(): string
    {
        return 'client/dist/bulma-grid.css';
    }

    /**
     * Builds the visibility class map from the active (possibly filtered) viewport set.
     *
     * Uses upward-scoped `is-hidden-{vp}` for all viewports (no `-only` suffix).
     * Non-last viewports restore visibility at the next enabled viewport with
     * `is-block-{next}`. The last enabled viewport has no restore class.
     *
     * @return array<string, list<string>>
     */
    private function buildVisibilityMap(): array
    {
        $map = [];
        $keys = array_keys($this->viewports);
        $count = count($keys);

        foreach ($keys as $index => $key) {
            $isLast = $index === $count - 1;

            if ($isLast) {
                $map[$key] = [sprintf('is-hidden-%s', $key)];
            } else {
                $nextKey = $keys[$index + 1];
                $map[$key] = [
                    sprintf('is-hidden-%s', $key),
                    sprintf('is-block-%s', $nextKey),
                ];
            }
        }

        return $map;
    }
}
