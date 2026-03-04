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
final class TailwindAdapter implements GridAdapterInterface
{
    use GridAdapterConfiguration;

    private const int DEFAULT_COLUMNS = 12;
    private const string DEFAULT_VIEWPORT_KEY = 'sm';

    /** @var array<string, Viewport> */
    private array $viewports;

    /** @var positive-int */
    private int $columnCount;

    private Viewport $defaultViewport;

    public function __construct()
    {
        $allViewports = [
            'sm' => new Viewport('sm', 'Small'),
            'md' => new Viewport('md', 'Medium'),
            'lg' => new Viewport('lg', 'Large'),
            'xl' => new Viewport('xl', 'Extra Large'),
            '2xl' => new Viewport('2xl', '2X Large'),
        ];

        $this->viewports = $this->applyViewportFilter($allViewports);
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
        return sprintf('%s:col-span-%d', $viewport, $width);
    }

    /**
     * Tailwind's col-start is 1-based, so an offset of N columns means col-start-(N+1).
     */
    public function getOffsetClass(string $viewport, int $offset): string
    {
        return sprintf('%s:col-start-%d', $viewport, $offset + 1);
    }

    /**
     * Visibility classes that hide an element at the given viewport.
     *
     * Tailwind uses `{vp}:hidden` to hide and `{next}:block` to restore.
     * "Next" means the next enabled viewport. Last has no restore.
     *
     * @return list<string>
     */
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
        return sprintf('grid grid-cols-%d', 12);
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

    public function getBaseWidthClass(int $width): string
    {
        return sprintf('col-span-%d', $width);
    }

    /**
     * Tailwind's col-start is 1-based, so an offset of N columns means col-start-(N+1).
     */
    public function getBaseOffsetClass(int $offset): string
    {
        return sprintf('col-start-%d', $offset + 1);
    }

    public function getCssPath(): ?string
    {
        return null;
    }
}
