<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Adapter;

use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

/**
 * Grid adapter for Bootstrap 5's Flexbox grid system.
 *
 * Implements Bootstrap's responsive column classes using its six default
 * breakpoints: xs (mobile-first default), sm (576px), md (768px), lg (992px),
 * xl (1200px), and xxl (1400px).
 *
 * Bootstrap treats xs as the mobile-first default — width classes use
 * `col-{n}` instead of `col-xs-{n}`, offset classes use `offset-{n}`
 * instead of `offset-xs-{n}`, and visibility uses `d-none` instead of
 * `d-xs-none`. All other viewports include the viewport infix.
 */
final readonly class BootstrapAdapter implements GridAdapterInterface
{
    /** @var array<string, Viewport> */
    private array $viewports;

    /**
     * Visibility class pairs keyed by viewport.
     *
     * Bootstrap's responsive display utilities differ by viewport position:
     * - First viewport (xs): `d-none` + `d-sm-block` — no viewport infix for the
     *   mobile-first default, restore at the next breakpoint.
     * - Middle viewports: `d-{vp}-none` + `d-{next}-block` — the infix scopes
     *   hiding from that breakpoint upward, the restore class re-enables
     *   visibility at the next one.
     * - Last viewport (xxl): `d-xxl-none` — no restore needed since there is
     *   no larger breakpoint.
     *
     * @var array<string, list<string>>
     */
    private array $visibilityMap;

    public function __construct()
    {
        $this->viewports = [
            'xs' => new Viewport('xs', 'Extra Small'),
            'sm' => new Viewport('sm', 'Small'),
            'md' => new Viewport('md', 'Medium'),
            'lg' => new Viewport('lg', 'Large'),
            'xl' => new Viewport('xl', 'Extra Large'),
            'xxl' => new Viewport('xxl', 'Extra Extra Large'),
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
        return 12;
    }

    public function getDefaultViewport(): Viewport
    {
        return $this->viewports['md'];
    }

    public function getWidthClass(string $viewport, int $width): string
    {
        if ($viewport === 'xs') {
            return sprintf('col-%d', $width);
        }

        return sprintf('col-%s-%d', $viewport, $width);
    }

    public function getOffsetClass(string $viewport, int $offset): string
    {
        if ($viewport === 'xs') {
            return sprintf('offset-%d', $offset);
        }

        return sprintf('offset-%s-%d', $viewport, $offset);
    }

    /** @return list<string> */
    public function getVisibilityClasses(string $viewport): array
    {
        return $this->visibilityMap[$viewport];
    }

    public function getRowClasses(): string
    {
        return 'row';
    }

    public function getContainerClass(bool $fluid): string
    {
        if ($fluid) {
            return 'container-fluid';
        }

        return 'container';
    }

    /** @return array<string, string> */
    public function getTitleClassOptions(): array
    {
        return [
            'display-1' => 'Display 1',
            'display-2' => 'Display 2',
            'display-3' => 'Display 3',
            'display-4' => 'Display 4',
            'display-5' => 'Display 5',
            'display-6' => 'Display 6',
            'h1' => 'Heading 1',
            'h2' => 'Heading 2',
            'h3' => 'Heading 3',
            'h4' => 'Heading 4',
            'h5' => 'Heading 5',
            'h6' => 'Heading 6',
        ];
    }

    public function getBaseWidthClass(int $width): string
    {
        return $this->getWidthClass('xs', $width);
    }

    public function getBaseOffsetClass(int $offset): string
    {
        return $this->getOffsetClass('xs', $offset);
    }

    public function getCssPath(): string
    {
        return 'client/dist/bootstrap-grid.css';
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
                // xs: no viewport infix for hide, restore at next viewport
                $map[$key] = [
                    'd-none',
                    sprintf('d-%s-block', $nextKey),
                ];
            } elseif ($isLast) {
                // Last viewport: just hide, nothing above to restore
                $map[$key] = [
                    sprintf('d-%s-none', $key),
                ];
            } elseif ($nextKey !== null) {
                // Middle viewports: hide with infix, restore at next
                $map[$key] = [
                    sprintf('d-%s-none', $key),
                    sprintf('d-%s-block', $nextKey),
                ];
            }
        }

        return $map;
    }
}
