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
final readonly class BulmaAdapter implements GridAdapterInterface
{
    private const string MOBILE_KEY = 'mobile';

    private const string DEFAULT_VIEWPORT_KEY = 'desktop';

    private const int COLUMN_COUNT = 12;

    /** @var array<string, Viewport> */
    private array $viewports;

    /**
     * Visibility class pairs keyed by viewport.
     *
     * Bulma's responsive helpers differ by viewport position:
     * - First viewport (mobile): `is-hidden-mobile` — no "-only" suffix because
     *   content is naturally visible from tablet upward.
     * - Middle viewports: `is-hidden-{vp}-only` + `is-block-{next}` — the "-only"
     *   suffix scopes hiding to exactly that breakpoint, the restore class re-enables
     *   visibility at the next one.
     * - Last viewport (fullhd): `is-hidden-fullhd` — no "-only" suffix needed since
     *   there is no larger breakpoint.
     *
     * @var array<string, list<string>>
     */
    private array $visibilityMap;

    public function __construct()
    {
        $this->viewports = [
            'mobile' => new Viewport('mobile', 'Mobile', null),
            'tablet' => new Viewport('tablet', 'Tablet', 769),
            'desktop' => new Viewport('desktop', 'Desktop', 1024),
            'widescreen' => new Viewport('widescreen', 'Widescreen', 1216),
            'fullhd' => new Viewport('fullhd', 'Full HD', 1408),
        ];

        $this->visibilityMap = $this->buildVisibilityMap();
    }

    /** @return list<Viewport> */
    public function getViewports(): array
    {
        return array_values($this->viewports);
    }

    /** @return positive-int */
    public function getColumnCount(): int
    {
        return self::COLUMN_COUNT;
    }

    public function getDefaultViewport(): Viewport
    {
        return $this->viewports[self::DEFAULT_VIEWPORT_KEY];
    }

    public function getWidthClass(string $viewport, int $width): string
    {
        if ($viewport === self::MOBILE_KEY) {
            return sprintf('is-%d', $width);
        }

        return sprintf('is-%d-%s', $width, $viewport);
    }

    public function getOffsetClass(string $viewport, int $offset): string
    {
        if ($viewport === self::MOBILE_KEY) {
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

    public function getCssPath(): string
    {
        return 'client/dist/bulma-grid.css';
    }

    /**
     * Builds the visibility class map based on viewport ordering.
     *
     * Derives hide/restore pairs programmatically from the viewport list
     * rather than hardcoding them, so the logic stays consistent if the
     * viewport set ever changes.
     *
     * @return array<string, list<string>>
     */
    private function buildVisibilityMap(): array
    {
        $map = [];
        $keys = array_keys($this->viewports);
        $lastIndex = count($keys) - 1;

        foreach ($keys as $index => $key) {
            $isFirst = $index === 0;
            $isLast = $index === $lastIndex;
            $nextKey = $keys[$index + 1] ?? null;

            if ($isFirst && $nextKey !== null) {
                // Mobile: hide at this viewport, restore at next
                $map[$key] = [
                    sprintf('is-hidden-%s', $key),
                    sprintf('is-block-%s', $nextKey),
                ];
            } elseif ($isLast) {
                // Last viewport: just hide, nothing above to restore
                $map[$key] = [
                    sprintf('is-hidden-%s', $key),
                ];
            } elseif ($nextKey !== null) {
                // Middle viewports: hide with -only suffix, restore at next
                $map[$key] = [
                    sprintf('is-hidden-%s-only', $key),
                    sprintf('is-block-%s', $nextKey),
                ];
            }
        }

        return $map;
    }
}
