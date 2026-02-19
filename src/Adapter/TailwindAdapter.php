<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Adapter;

use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

/**
 * Tailwind CSS grid adapter using utility classes for a 12-column CSS Grid layout.
 *
 * Viewport breakpoints match Tailwind v3/v4 defaults (sm through 2xl).
 * All class generation is stateless — no DOM, no config file needed.
 */
final readonly class TailwindAdapter implements GridAdapterInterface
{
    private const string DEFAULT_VIEWPORT_KEY = 'sm';

    private const int COLUMN_COUNT = 12;

    /** @var array<string, Viewport> */
    private array $viewports;

    public function __construct()
    {
        $this->viewports = [
            'sm' => new Viewport('sm', 'Small', 640),
            'md' => new Viewport('md', 'Medium', 768),
            'lg' => new Viewport('lg', 'Large', 1024),
            'xl' => new Viewport('xl', 'Extra Large', 1280),
            '2xl' => new Viewport('2xl', '2X Large', 1536),
        ];
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
        return sprintf('%s:col-span-%d', $viewport, $width);
    }

    /**
     * Tailwind's col-start is 1-based, so an offset of N columns means col-start-(N+1).
     */
    public function getOffsetClass(string $viewport, int $offset): string
    {
        return sprintf('%s:col-start-%d', $viewport, $offset + 1);
    }

    /** @return list<string> */
    public function getVisibilityClasses(string $viewport): array
    {
        $keys = array_keys($this->viewports);
        $index = array_search($viewport, $keys, true);
        $isLast = $index === count($keys) - 1;

        if ($isLast) {
            return [sprintf('%s:hidden', $viewport)];
        }

        $nextKey = $keys[$index + 1];

        return [
            sprintf('%s:hidden', $viewport),
            sprintf('%s:block', $nextKey),
        ];
    }

    public function getRowClasses(): string
    {
        return sprintf('grid grid-cols-%d', self::COLUMN_COUNT);
    }

    public function getContainerClass(bool $fluid): string
    {
        return $fluid ? 'w-full' : 'container mx-auto';
    }

    /** @return array<string, string> */
    public function getTitleClassOptions(): array
    {
        return [
            'text-4xl' => 'Heading 1',
            'text-3xl' => 'Heading 2',
            'text-2xl' => 'Heading 3',
            'text-xl' => 'Heading 4',
            'text-lg' => 'Heading 5',
            'text-base' => 'Heading 6',
        ];
    }

    public function getCssPath(): ?string
    {
        return null;
    }
}
