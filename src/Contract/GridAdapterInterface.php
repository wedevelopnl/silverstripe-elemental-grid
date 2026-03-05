<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use WeDevelop\Grid\Value\Viewport;

/**
 * Stateless adapter that translates grid layout intent into framework-specific CSS classes.
 *
 * Methods receive viewport keys and numeric values, return CSS class strings.
 * No data model dependency — the adapter has no knowledge of elements.
 *
 * Each adapter defines its own viewport set via {@see getViewports()}. Class
 * generation methods accept string viewport keys from that set, keeping the API
 * aligned with the JSON grid settings stored on Column.
 */
interface GridAdapterInterface
{
    /**
     * Viewport definitions supported by this adapter, ordered smallest to largest.
     *
     * @return list<Viewport>
     */
    public function getViewports(): array;

    /**
     * Total number of columns in the grid system (typically 12).
     *
     * @return positive-int
     */
    public function getColumnCount(): int;

    /**
     * Suggested default viewport for the CMS grid editor.
     */
    public function getDefaultViewport(): Viewport;

    /**
     * Width class for the given viewport and column span.
     *
     * @example Bootstrap: getWidthClass('md', 6) → 'col-md-6'
     * @example Tailwind:  getWidthClass('md', 6) → 'md:col-span-6'
     * @example Bulma:     getWidthClass('desktop', 6) → 'is-6-desktop'
     */
    public function getWidthClass(string $viewport, int $width): string;

    /**
     * Offset class for the given viewport and column offset.
     *
     * @example Bootstrap: getOffsetClass('md', 3) → 'offset-md-3'
     * @example Tailwind:  getOffsetClass('md', 3) → 'md:col-start-4'
     * @example Bulma:     getOffsetClass('desktop', 3) → 'is-offset-3-desktop'
     */
    public function getOffsetClass(string $viewport, int $offset): string;

    /**
     * Visibility classes that hide an element at the given viewport.
     *
     * Returns a list because hiding typically requires paired classes: one to
     * hide at the target viewport, one to restore visibility at the next
     * breakpoint. For the last viewport only a single hide class is needed.
     *
     * @example Bootstrap xs: ['d-none', 'd-sm-block']
     * @example Bootstrap xl: ['d-xl-none']
     *
     * @return list<string>
     */
    public function getVisibilityClasses(string $viewport): array;

    /**
     * CSS classes for a grid row container.
     *
     * @example Bootstrap: 'row'
     * @example Tailwind:  'grid grid-cols-12'
     * @example Bulma:     'columns is-multiline'
     */
    public function getRowClasses(): string;

    /**
     * CSS class for the outermost grid container.
     *
     * @param bool $fluid Whether the container should span full width
     *
     * @example Bootstrap: getContainerClass(false) → 'container'
     * @example Bootstrap: getContainerClass(true) → 'container-fluid'
     */
    public function getContainerClass(bool $fluid): string;

    /**
     * Dropdown source for element title CSS class selection.
     *
     * Keys are the CSS values, values are human-readable labels —
     * matching the SilverStripe DropdownField source convention.
     *
     * @return array<string, string>
     */
    public function getTitleClassOptions(): array;

    /**
     * Base width class that applies regardless of viewport (for CMS editor preview).
     *
     * The CMS grid editor's viewport is uncontrolled — it renders at whatever
     * size the panel happens to be. Base classes ensure columns always apply
     * without requiring a specific screen width.
     *
     * @example Bootstrap: getBaseWidthClass(6) → 'col-6'
     * @example Tailwind:  getBaseWidthClass(6) → 'col-span-6'
     * @example Bulma:     getBaseWidthClass(6) → 'is-6'
     */
    public function getBaseWidthClass(int $width): string;

    /**
     * Base offset class that applies regardless of viewport (for CMS editor preview).
     *
     * @example Bootstrap: getBaseOffsetClass(3) → 'offset-3'
     * @example Tailwind:  getBaseOffsetClass(3) → 'col-start-4'
     * @example Bulma:     getBaseOffsetClass(3) → 'is-offset-3'
     */
    public function getBaseOffsetClass(int $offset): string;

    /**
     * Filesystem path to a fallback CSS file for CMS preview rendering.
     *
     * Returns null if the framework does not require a bundled fallback
     * (e.g. when the CMS already loads the framework).
     */
    public function getCssPath(): ?string;
}
