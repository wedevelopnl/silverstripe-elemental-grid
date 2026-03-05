<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

/**
 * Adapter-defined viewport breakpoint.
 *
 * Each grid adapter declares its own viewport set — Bootstrap 5 has 6
 * (xs through xxl), Tailwind has 5 (sm through 2xl), Bulma uses entirely
 * different names (mobile, tablet, desktop, …). A value object lets each
 * adapter define exactly the viewports it needs rather than forcing every
 * framework into a single fixed enum.
 */
final readonly class Viewport
{
    /**
     * @param string $key   Adapter-defined key, e.g. 'md', 'desktop', '2xl'
     * @param string $label Human-readable label, e.g. 'Medium', 'Desktop'
     */
    public function __construct(
        public string $key,
        public string $label,
    ) {}
}
