<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

/**
 * Central grid configuration service — the preferred injection target for all grid-aware code.
 *
 * Wraps the active {@see GridAdapterInterface} adapter and applies site-level
 * YAML overrides (e.g. custom column count or default viewport). Consumers
 * should inject this interface rather than the raw adapter.
 */
interface GridConfigServiceInterface
{
    /**
     * Viewport definitions supported by the active adapter, ordered smallest to largest.
     *
     * @return list<Viewport>
     */
    public function getViewports(): array;

    /**
     * Total number of columns in the grid system.
     *
     * May be overridden via YAML configuration.
     *
     * @return positive-int
     */
    public function getColumnCount(): int;

    /**
     * Default viewport for the CMS grid editor.
     *
     * May be overridden via YAML configuration using a viewport key string.
     */
    public function getDefaultViewport(): Viewport;

    /**
     * Width class for the given viewport and column span.
     */
    public function getWidthClass(string $viewport, int $width): string;

    /**
     * Offset class for the given viewport and column offset.
     */
    public function getOffsetClass(string $viewport, int $offset): string;

    /**
     * Visibility classes that hide an element at the given viewport.
     *
     * @return list<string>
     */
    public function getVisibilityClasses(string $viewport): array;

    /**
     * CSS classes for a grid row container.
     */
    public function getRowClasses(): string;

    /**
     * CSS class for the outermost grid container.
     */
    public function getContainerClass(bool $fluid): string;

    /**
     * Dropdown source for element title CSS class selection.
     *
     * @return array<string, string>
     */
    public function getTitleClassOptions(): array;

    /**
     * Filesystem path to a fallback CSS file for CMS preview rendering.
     */
    public function getCssPath(): ?string;
}
