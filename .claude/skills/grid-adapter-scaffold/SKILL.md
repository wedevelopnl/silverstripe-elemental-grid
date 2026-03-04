---
name: grid-adapter-scaffold
description: Scaffolds a new GridAdapterInterface implementation with the GridAdapterConfiguration trait, all required method stubs, viewport definitions, visibility map logic, and YAML config registration. Use when creating a new CSS framework adapter for the grid system.
---

This skill generates a complete grid adapter implementation for a new CSS framework. It follows the exact patterns established by the existing Bootstrap, Tailwind, and Bulma adapters.

## Step 1: Gather Requirements

Ask the user for:
1. **CSS framework name** (e.g., "Foundation", "Skeleton", "PureCSS")
2. **Viewport breakpoints**: List of viewport keys and labels (e.g., `sm → Small`, `md → Medium`, `lg → Large`)
3. **Default viewport**: Which viewport is the default (most commonly used)
4. **Column count**: Total grid columns (typically 12)
5. **Base viewport behavior**: Does the framework have a "mobile-first" base viewport with no prefix in class names? (like Bootstrap's `xs` or Bulma's `mobile`)
6. **Class name patterns**: Ask for examples of:
   - Width class (e.g., Bootstrap: `col-md-6`, Tailwind: `md:col-span-6`)
   - Offset class (e.g., Bootstrap: `offset-md-3`, Tailwind: `md:col-start-4`)
   - Visibility hide/show classes
   - Row container class
   - Container class (fixed and fluid variants)
7. **CSS path**: Does the adapter bundle a CSS file (`client/dist/xyz-grid.css`) or rely on the project's own build (return `null` from `getCssPath()`)?
8. **Title class options**: What heading/display classes does the framework offer?

## Step 2: Generate the Adapter Class

Create `src/Adapter/{Name}Adapter.php` with this structure:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Adapter;

use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

final class {Name}Adapter implements GridAdapterInterface
{
    use GridAdapterConfiguration;

    private const int DEFAULT_COLUMNS = {columnCount};
    private const string DEFAULT_VIEWPORT_KEY = '{defaultKey}';

    /** @var array<string, Viewport> */
    private readonly array $viewports;

    /** @var array<string, list<string>> */
    private readonly array $visibilityMap;

    private readonly int $columnCount;
    private readonly Viewport $defaultViewport;

    public function __construct()
    {
        $allViewports = [
            // ... Viewport instances
        ];

        $this->viewports       = $this->applyViewportFilter($allViewports);
        $this->visibilityMap   = $this->buildVisibilityMap();
        $this->columnCount     = $this->resolveColumnCount(self::DEFAULT_COLUMNS);
        $this->defaultViewport = $this->resolveDefaultViewport(self::DEFAULT_VIEWPORT_KEY, $this->viewports);
    }

    // ... all 12 interface methods
}
```

### Required Methods

Implement all 12 `GridAdapterInterface` methods:
- `getViewports()` → `return array_values($this->viewports);`
- `getColumnCount()` → `return $this->columnCount;`
- `getDefaultViewport()` → `return $this->defaultViewport;`
- `getWidthClass(string $viewport, int $width)` → framework-specific width class
- `getOffsetClass(string $viewport, int $offset)` → framework-specific offset class
- `getBaseWidthClass(int $width)` → width class for the base/default viewport
- `getBaseOffsetClass(int $offset)` → offset class for the base/default viewport
- `getVisibilityClasses(string $viewport)` → `return $this->visibilityMap[$viewport] ?? [];`
- `getRowClasses()` → row container class string
- `getContainerClass(bool $fluid)` → container class (fixed vs fluid)
- `getTitleClassOptions()` → heading class → label mapping
- `getCssPath()` → path to bundled CSS or `null`

### Visibility Map Pattern

The `buildVisibilityMap()` private method generates hide/restore pairs:
- For non-last viewports: `[hideClass, restoreAtNextViewportClass]`
- For the last viewport: `[hideClass]`

Handle base viewports (no prefix) as special cases.

## Step 3: Register in YAML

Update `_config/grid.yml` or create a separate YAML file. Show the user the config to switch to the new adapter:

```yaml
SilverStripe\Core\Injector\Injector:
  WeDevelop\ElementalGrid\Contract\GridAdapterInterface:
    class: WeDevelop\ElementalGrid\Adapter\{Name}Adapter
```

## Step 4: Verify

After generating the adapter:
1. Run `make analyse` to verify PHPStan compliance (level max, 100% type coverage)
2. Check that all method return types satisfy the interface
3. Verify the visibility map generates correct hide/restore pairs for all viewport combinations

## Reference

Study the existing adapters for patterns:
- `src/Adapter/BootstrapAdapter.php` — Bootstrap 5 (base viewport: `xs`, no infix)
- `src/Adapter/TailwindAdapter.php` — Tailwind v3/v4 (all prefixed, offset uses `col-start-{n+1}`)
- `src/Adapter/BulmaAdapter.php` — Bulma (base viewport: `mobile`, uses suffix instead of prefix)
- `src/Adapter/GridAdapterConfiguration.php` — Shared trait with YAML-configurable overrides
