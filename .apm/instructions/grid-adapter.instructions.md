---
description: Grid adapter architecture, interface contract, and how to implement new CSS framework adapters
applyTo: "**/*"
---

# Grid Adapter System

## Architecture

Grid adapters translate the abstract grid model (viewports, column widths, offsets, visibility) into CSS framework-specific class names. All consumers depend on `GridAdapterInterface`, never on a concrete adapter.

### Key Files

- `src/Contract/GridAdapterInterface.php` — 12 methods defining the adapter contract
- `src/Adapter/GridAdapterConfiguration.php` — Trait providing YAML-configurable overrides
- `src/Contract/Viewport.php` — Value object (`final readonly class`, not an enum)
- `src/Contract/ContainerType.php` — Enum: `Section`, `Row`, `Column`
- `_config/grid.yml` — DI binding (default: `BootstrapAdapter`)

### Existing Adapters

| Adapter | Default Columns | Default Viewport | Viewports |
|---------|----------------|------------------|-----------|
| `BootstrapAdapter` | 12 | `md` | xs, sm, md, lg, xl, xxl |
| `TailwindAdapter` | 12 | `sm` | sm, md, lg, xl, 2xl |
| `BulmaAdapter` | 12 | `desktop` | mobile, tablet, desktop, widescreen, fullhd |

## Implementing a New Adapter

### 1. Create the Adapter Class

```php
namespace WeDevelop\ElementalGrid\Adapter;

use WeDevelop\ElementalGrid\Contract\GridAdapterInterface;
use WeDevelop\ElementalGrid\Contract\Viewport;

final class YourAdapter implements GridAdapterInterface
{
    use GridAdapterConfiguration;

    private const int DEFAULT_COLUMNS = 12;
    private const string DEFAULT_VIEWPORT_KEY = 'md';

    /** @var array<string, Viewport> */
    private readonly array $viewports;
    private readonly int $columnCount;
    private readonly Viewport $defaultViewport;

    public function __construct()
    {
        $allViewports = [
            'sm' => new Viewport('sm', 'Small'),
            'md' => new Viewport('md', 'Medium'),
            'lg' => new Viewport('lg', 'Large'),
        ];

        $this->viewports       = $this->applyViewportFilter($allViewports);
        $this->columnCount     = $this->resolveColumnCount(self::DEFAULT_COLUMNS);
        $this->defaultViewport = $this->resolveDefaultViewport(self::DEFAULT_VIEWPORT_KEY, $this->viewports);
    }

    // Implement all 12 interface methods...
}
```

### 2. `GridAdapterConfiguration` Trait

The trait provides three YAML-configurable properties (set on the concrete adapter class in project YAML):

| Property | Type | Default | Purpose |
|----------|------|---------|---------|
| `$enabled_viewports` | `list<string>\|null` | `null` (all active) | Restrict which viewports are available |
| `$total_columns` | `int\|null` | `null` (adapter default) | Override total column count |
| `$default_viewport` | `string\|null` | `null` (adapter default) | Override default viewport key |

The trait provides three helper methods to call in `__construct()`:

- `applyViewportFilter(array $allViewports): array` — Filters the full viewport map to only enabled viewports. Throws `InvalidGridValueException` if empty array or unknown key.
- `resolveColumnCount(int $adapterDefault): int` — Returns YAML override if set, else adapter default. Throws if override is `<= 0`.
- `resolveDefaultViewport(string $adapterDefaultKey, array $viewports): Viewport` — Resolves the effective default viewport. Throws if key not found in active viewports.

### 3. Interface Methods to Implement

| Method | Returns | Notes |
|--------|---------|-------|
| `getViewports()` | `list<Viewport>` | Return `array_values($this->viewports)` |
| `getColumnCount()` | `positive-int` | Return `$this->columnCount` |
| `getDefaultViewport()` | `Viewport` | Return `$this->defaultViewport` |
| `getWidthClass($viewport, $width)` | `string` | Framework-specific width class |
| `getOffsetClass($viewport, $offset)` | `string` | Framework-specific offset class |
| `getBaseWidthClass($width)` | `string` | Width class for base/default viewport |
| `getBaseOffsetClass($offset)` | `string` | Offset class for base/default viewport |
| `getVisibilityClasses($viewport)` | `list<string>` | Hide/restore pair for the viewport |
| `getRowClasses()` | `string` | Row container classes |
| `getContainerClass($fluid)` | `string` | Container wrapper classes |
| `getTitleClassOptions()` | `array<string, string>` | CSS class → human label mapping |
| `getCssPath()` | `?string` | Path to bundled CSS, or `null` if framework handles it |

### 4. Visibility Classes Pattern

All adapters generate hide+restore pairs. For a viewport that is NOT the last active viewport:
- First class: hides from this viewport upward
- Second class: restores at the next active viewport

For the last active viewport, only the hide class is needed.

Some frameworks have a "base" viewport with no prefix (Bootstrap's `xs`, Bulma's `mobile`) — handle these as special cases in class generation.

### 5. Register the Adapter

In `_config/grid.yml` (or project-level YAML):

```yaml
SilverStripe\Core\Injector\Injector:
  WeDevelop\ElementalGrid\Contract\GridAdapterInterface:
    class: WeDevelop\ElementalGrid\Adapter\YourAdapter
```

### 6. Optional: YAML Configuration

```yaml
WeDevelop\ElementalGrid\Adapter\YourAdapter:
  enabled_viewports:
    - sm
    - md
    - lg
  total_columns: 16
  default_viewport: md
```
