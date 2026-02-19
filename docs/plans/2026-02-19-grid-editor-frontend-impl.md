# Grid Editor Frontend Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the proof-of-life `<ul>` tree with a read-only visual grid editor using the "Layered Blocks" design — sections, rows, columns rendered as a real Bootstrap grid with publication state indicators, a viewport switcher, and fraction badges.

**Architecture:** The GridEditor component is decomposed into small, single-responsibility components (ViewportSwitcher, SectionBlock, RowBlock, ColumnBlock, ElementCard, EmptyState). Grid layout uses actual Bootstrap CSS classes served by the backend adapter via `getClientConfig()`. The frontend does zero class generation — it reads pre-computed class lookup maps from `window.ss.config`. A `useViewport` hook manages the active viewport state. This ensures custom adapters automatically surface their config to the frontend without any frontend code changes.

**Tech Stack:** React 18, TypeScript 5.9, Vitest + React Testing Library, SCSS (BEM), Bootstrap 4 grid classes (from SS admin), Zod validation

**Design doc:** `docs/plans/2026-02-19-grid-editor-frontend-design.md`

---

## Task 1: Add `gridSettings` to the column node in API response and Zod schema

The `readTree` API response needs to include `gridSettings` on column nodes. The backend `ElementNode` DTO must be updated, and the frontend Zod schema must expect it. E2E fixtures must also be updated with meaningful `GridSettings` values so that later E2E tests can assert viewport switching behavior.

**Files:**

- Modify: `src/Model/ElementNode.php` (PHPStan type, constructor, serialize)
- Modify: `src/Service/ElementTreeBuilder.php` (pass gridSettings to column nodes)
- Modify: `client/src/types/elements.ts` (columnNodeSchema)
- Modify: `tests/E2E/Fixture/ElementTree.yml` (add GridSettings to columns)
- Modify: `tests/E2E/Fixture/ComplexPage.yml` (add GridSettings to columns)
- Test: `client/src/tests/types/elements.test.ts`
- Test: `tests/Unit/Model/ElementNodeTest.php` (create if needed)

### Step 1: Write the failing PHP unit test

Create or extend the test for `ElementNode` serialization. The test verifies that column container nodes include `gridSettings` in their JSON output.

```php
public function testColumnNodeIncludesGridSettings(): void
{
    $gridSettings = [
        'xs' => ['width' => 12, 'offset' => 0, 'visible' => true],
        'md' => ['width' => 6, 'offset' => 0, 'visible' => true],
    ];

    $node = new ElementNode(
        id: 1,
        title: 'Test Column',
        blockSchema: ['typeName' => 'Column', 'actions' => ['edit' => '/edit/1'], 'content' => ''],
        obsoleteClassName: null,
        version: 1,
        isPublished: false,
        isLiveVersion: false,
        canDelete: true,
        canPublish: true,
        canUnpublish: false,
        canCreate: true,
        statusFlags: [],
        containerType: ContainerType::Column,
        allowedTypes: null,
        children: [],
        gridSettings: $gridSettings,
    );

    $serialized = $node->jsonSerialize();
    self::assertArrayHasKey('gridSettings', $serialized);
    self::assertSame($gridSettings, $serialized['gridSettings']);
}

public function testNonColumnNodeOmitsGridSettings(): void
{
    $node = new ElementNode(
        id: 2,
        title: 'Test Row',
        blockSchema: ['typeName' => 'Row', 'actions' => ['edit' => '/edit/2'], 'content' => ''],
        obsoleteClassName: null,
        version: 1,
        isPublished: false,
        isLiveVersion: false,
        canDelete: true,
        canPublish: true,
        canUnpublish: false,
        canCreate: true,
        statusFlags: [],
        containerType: ContainerType::Row,
        allowedTypes: null,
        children: [],
    );

    $serialized = $node->jsonSerialize();
    self::assertArrayNotHasKey('gridSettings', $serialized);
}
```

### Step 2: Run test to verify it fails

Run: `make test-unit`
Expected: FAIL — `ElementNode` constructor does not accept `gridSettings` parameter

### Step 3: Update `ElementNode` PHP DTO

In `src/Model/ElementNode.php`:

- Add to PHPStan type: `gridSettings?: array<string, array{width: int, offset: int, visible: bool}>|null`
- Add constructor parameter: `public ?array $gridSettings = null`
- In `jsonSerialize()`, add `gridSettings` to the output only when `containerType === ContainerType::Column` and `gridSettings !== null`

### Step 4: Update `ElementTreeBuilder` to pass grid settings

In `src/Service/ElementTreeBuilder.php`, in the `buildElementNode()` method:

- After the existing container check, if the element is an `ElementColumn`, call `$element->getGridSettingsData()` and pass it as the `gridSettings` parameter to `ElementNode`.

### Step 5: Run PHP test to verify it passes

Run: `make test-unit`
Expected: PASS

### Step 6: Write the failing frontend test

In `client/src/tests/types/elements.test.ts`, add a test that verifies `columnNodeSchema` parses a node with `gridSettings`:

```typescript
it('parses column node with gridSettings', () => {
  const input = {
    id: 3,
    title: 'Left Column',
    containerType: 'column',
    allowedTypes: null,
    children: [],
    gridSettings: {
      xs: { width: 12, offset: 0, visible: true },
      md: { width: 6, offset: 0, visible: true },
    },
    blockSchema: { typeName: 'Column', actions: { edit: '/edit/3' }, content: '' },
    obsoleteClassName: null,
    version: 1,
    isPublished: false,
    isLiveVersion: false,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
  };

  const result = columnNodeSchema.parse(input);
  expect(result.gridSettings).toEqual(input.gridSettings);
});
```

### Step 7: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/types/elements.test.ts`
Expected: FAIL — `gridSettings` is not defined in the schema

### Step 8: Update the Zod schema

In `client/src/types/elements.ts`:

Add a `gridSettingsSchema`:

```typescript
const viewportSettingsSchema = z.object({
  width: z.number().int(),
  offset: z.number().int(),
  visible: z.boolean(),
});

export const gridSettingsSchema = z.record(z.string(), viewportSettingsSchema);
```

Add `gridSettings` to `columnNodeSchema`:

```typescript
export const columnNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('column'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(simpleElementNodeSchema).nullable(),
  gridSettings: gridSettingsSchema,
});
```

Export the inferred types:

```typescript
export type GridSettings = z.infer<typeof gridSettingsSchema>;
export type ViewportSettings = z.infer<typeof viewportSettingsSchema>;
```

### Step 9: Update test fixtures

Add `gridSettings` to the column node in `client/src/tests/components/GridEditor/GridEditor.test.tsx` mock data so existing tests don't break:

```typescript
gridSettings: {
  xs: { width: 12, offset: 0, visible: true },
  sm: { width: 12, offset: 0, visible: true },
  md: { width: 12, offset: 0, visible: true },
  lg: { width: 12, offset: 0, visible: true },
  xl: { width: 12, offset: 0, visible: true },
},
```

### Step 10: Update E2E fixtures with meaningful GridSettings

The existing E2E fixtures have no `GridSettings` on their columns, so `onBeforeWrite` defaults apply (all viewports full-width). Update both fixtures with varied settings so E2E tests can assert viewport switching, hidden columns, and offsets.

**`tests/E2E/Fixture/ElementTree.yml`** — update the `ElementColumn` section:

```yaml
WeDevelop\ElementalGrid\Elements\ElementColumn:
  col1:
    Title: 'Left Column'
    Sort: 1
    Parent: =>DNADesign\Elemental\Models\ElementalArea.row1_area
    ChildArea: =>DNADesign\Elemental\Models\ElementalArea.col1_area
    GridSettings: '{"xs":{"width":12,"offset":0,"visible":true},"sm":{"width":12,"offset":0,"visible":true},"md":{"width":8,"offset":0,"visible":true},"lg":{"width":6,"offset":0,"visible":true},"xl":{"width":6,"offset":0,"visible":true},"xxl":{"width":6,"offset":0,"visible":true}}'
  col2:
    Title: 'Right Column'
    Sort: 2
    Parent: =>DNADesign\Elemental\Models\ElementalArea.row1_area
    ChildArea: =>DNADesign\Elemental\Models\ElementalArea.col2_area
    GridSettings: '{"xs":{"width":12,"offset":0,"visible":false},"sm":{"width":12,"offset":0,"visible":false},"md":{"width":4,"offset":0,"visible":true},"lg":{"width":6,"offset":0,"visible":true},"xl":{"width":6,"offset":0,"visible":true},"xxl":{"width":6,"offset":0,"visible":true}}'
```

This gives testable viewport transitions:

| Viewport | col1 | col2 |
|----------|------|------|
| xs, sm | 12/12 | **hidden** |
| md | 8/12 | 4/12 |
| lg, xl, xxl | 6/12 | 6/12 |

**`tests/E2E/Fixture/ComplexPage.yml`** — same `GridSettings` on its columns (identical split; the publication state variety is what matters for this fixture, not column widths).

**Note:** `ElementColumn::$default_grid_settings` currently covers 5 viewports (xs–xl) but the `BootstrapAdapter` defines 6 (includes `xxl`). The ColumnBlock fallback logic handles missing viewport keys gracefully, but aligning the default should be done as a follow-up (out of scope for this plan).

### Step 11: Run all tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 12: Commit

```bash
git add src/Model/ElementNode.php src/Service/ElementTreeBuilder.php \
  client/src/types/elements.ts \
  client/src/tests/types/elements.test.ts \
  client/src/tests/components/GridEditor/GridEditor.test.tsx \
  tests/Unit/Model/ElementNodeTest.php \
  tests/E2E/Fixture/ElementTree.yml \
  tests/E2E/Fixture/ComplexPage.yml
git commit -m "feat(grid): include gridSettings in column node API response and Zod schema"
```

---

## Task 2: Expose adapter config via `getClientConfig()` on the PHP controller

The frontend needs the active adapter's viewport definitions, column count, row classes, and pre-computed CSS class lookup maps. These are static for the lifetime of the admin session, so they belong in `window.ss.config` — not in a separate API endpoint.

**Files:**

- Modify: `src/Controllers/ElementalGridController.php:234-242` (extend `getClientConfig()`)
- Test: `tests/Unit/Controllers/ElementalGridControllerTest.php` (create or extend)

### Step 1: Write the failing test

Test that `getClientConfig()` returns a `gridAdapter` key containing the expected shape.

```php
public function testGetClientConfigIncludesGridAdapter(): void
{
    $controller = new ElementalGridController();
    $config = $controller->getClientConfig();

    self::assertArrayHasKey('gridAdapter', $config);

    $adapter = $config['gridAdapter'];
    self::assertArrayHasKey('viewports', $adapter);
    self::assertArrayHasKey('defaultViewport', $adapter);
    self::assertArrayHasKey('columnCount', $adapter);
    self::assertArrayHasKey('rowClasses', $adapter);
    self::assertArrayHasKey('baseWidthClasses', $adapter);
    self::assertArrayHasKey('baseOffsetClasses', $adapter);
}
```

### Step 2: Run test to verify it fails

Run: `make test-unit`
Expected: FAIL — `gridAdapter` key not present

### Step 3: Implement the adapter config in `getClientConfig()`

In `src/Controllers/ElementalGridController.php`, extend `getClientConfig()`:

- Inject (or instantiate) the `GridAdapterInterface` implementation. For this iteration, instantiate `BootstrapAdapter` directly. (DI registration is a separate task.)
- Call the adapter methods to build the config:
  - `viewports`: map `getViewports()` to `[{key, label, minWidth}, ...]`
  - `defaultViewport`: `getDefaultViewport()->key`
  - `columnCount`: `getColumnCount()`
  - `rowClasses`: `getRowClasses()`
  - `baseWidthClasses`: for each width 1 through columnCount, call `getWidthClass(firstViewportKey, width)` — this produces unprefixed base classes for Bootstrap's xs viewport
  - `baseOffsetClasses`: for each offset 0 through (columnCount - 1), call `getOffsetClass(firstViewportKey, offset)`

The first viewport key (the one with `minWidth === null`, i.e. `xs` for Bootstrap) produces unprefixed base classes. If no viewport has null minWidth, use the first viewport in the list.

```php
$clientConfig['gridAdapter'] = [
    'viewports' => array_map(
        static fn (Viewport $vp) => [
            'key' => $vp->key,
            'label' => $vp->label,
            'minWidth' => $vp->minWidth,
        ],
        $adapter->getViewports(),
    ),
    'defaultViewport' => $adapter->getDefaultViewport()->key,
    'columnCount' => $adapter->getColumnCount(),
    'rowClasses' => $adapter->getRowClasses(),
    'baseWidthClasses' => $this->buildBaseWidthClasses($adapter),
    'baseOffsetClasses' => $this->buildBaseOffsetClasses($adapter),
];
```

### Step 4: Run tests

Run: `make test-unit`
Expected: PASS

### Step 5: Commit

```bash
git add src/Controllers/ElementalGridController.php \
  tests/Unit/Controllers/ElementalGridControllerTest.php
git commit -m "feat(grid): expose adapter config via getClientConfig for frontend consumption"
```

---

## Task 3: Add adapter config types and accessor to the frontend

The frontend needs TypeScript types for the adapter config shape, a Zod schema for validation, and an accessor function in `client/src/api/config.ts` to read it from `window.ss.config`.

**Files:**

- Create: `client/src/types/adapter.ts` (Zod schema + types)
- Modify: `client/src/types/index.ts` (add export)
- Modify: `client/src/types/silverstripe.d.ts` (add `gridAdapter` to section config type)
- Modify: `client/src/api/config.ts` (add `getAdapterConfig()`)
- Test: `client/src/tests/types/adapter.test.ts`
- Test: `client/src/tests/api/config.test.ts` (extend)

### Step 1: Write the failing test for the Zod schema

```typescript
// client/src/tests/types/adapter.test.ts
import { adapterConfigSchema } from '@/types/adapter';

describe('adapterConfigSchema', () => {
  it('parses valid adapter config', () => {
    const input = {
      viewports: [
        { key: 'xs', label: 'Extra Small', minWidth: null },
        { key: 'md', label: 'Medium', minWidth: 768 },
      ],
      defaultViewport: 'md',
      columnCount: 12,
      rowClasses: 'row',
      baseWidthClasses: { '1': 'col-1', '2': 'col-2', '12': 'col-12' },
      baseOffsetClasses: { '0': '', '1': 'offset-1', '11': 'offset-11' },
    };

    const result = adapterConfigSchema.parse(input);
    expect(result.defaultViewport).toBe('md');
    expect(result.columnCount).toBe(12);
    expect(result.baseWidthClasses['12']).toBe('col-12');
  });

  it('rejects missing required fields', () => {
    expect(() => adapterConfigSchema.parse({})).toThrow();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/types/adapter.test.ts`
Expected: FAIL — module does not exist

### Step 3: Implement the Zod schema and types

Create `client/src/types/adapter.ts`:

```typescript
import { z } from 'zod';

const viewportConfigSchema = z.object({
  key: z.string(),
  label: z.string(),
  minWidth: z.number().int().nullable(),
});

export const adapterConfigSchema = z.object({
  viewports: z.array(viewportConfigSchema),
  defaultViewport: z.string(),
  columnCount: z.number().int().positive(),
  rowClasses: z.string(),
  baseWidthClasses: z.record(z.string(), z.string()),
  baseOffsetClasses: z.record(z.string(), z.string()),
});

export type ViewportConfig = z.infer<typeof viewportConfigSchema>;
export type AdapterConfig = z.infer<typeof adapterConfigSchema>;
```

### Step 4: Update `silverstripe.d.ts`

Add `gridAdapter` to `SilverStripeSectionConfig`:

```typescript
export interface SilverStripeSectionConfig {
  name: string;
  url: string;
  controllerLink: string;
  gridAdapter?: unknown; // Validated via Zod at runtime
  [key: string]: unknown;
}
```

### Step 5: Add `getAdapterConfig()` to `client/src/api/config.ts`

```typescript
import { adapterConfigSchema, type AdapterConfig } from '@/types/adapter';

export function getAdapterConfig(): AdapterConfig {
  const config = getConfig();
  const section = config.sections.find((s) => s.name === CONTROLLER_FQCN);

  if (section === undefined) {
    throw new ConfigError(
      `Controller section "${CONTROLLER_FQCN}" not found in CMS config.`,
    );
  }

  return adapterConfigSchema.parse(section.gridAdapter);
}
```

### Step 6: Write tests for `getAdapterConfig()`

Extend `client/src/tests/api/config.test.ts` to test `getAdapterConfig()` reads from `window.ss.config` and validates through Zod.

### Step 7: Update barrel export

In `client/src/types/index.ts`, add:

```typescript
export * from './adapter';
```

### Step 8: Run all tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 9: Commit

```bash
git add client/src/types/adapter.ts client/src/types/index.ts \
  client/src/types/silverstripe.d.ts client/src/api/config.ts \
  client/src/tests/types/adapter.test.ts client/src/tests/api/config.test.ts
git commit -m "feat(grid): add adapter config types, Zod schema, and CMS config accessor"
```

---

## Task 4: Create the `useViewport` hook

A hook that manages the currently selected viewport key, reads the available viewports from the adapter config, and provides class lookup helpers. Components use this to determine which `GridSettings` values to read.

**Files:**

- Create: `client/src/hooks/useViewport.ts`
- Test: `client/src/tests/hooks/useViewport.test.ts`
- Modify: `client/src/hooks/index.ts` (add export)

### Step 1: Write the failing test

```typescript
import { renderHook, act } from '@testing-library/react';
import { useViewport } from '@/hooks/useViewport';

vi.mock('@/api/config', () => ({
  getAdapterConfig: () => ({
    viewports: [
      { key: 'xs', label: 'Extra Small', minWidth: null },
      { key: 'sm', label: 'Small', minWidth: 576 },
      { key: 'md', label: 'Medium', minWidth: 768 },
      { key: 'lg', label: 'Large', minWidth: 992 },
      { key: 'xl', label: 'Extra Large', minWidth: 1200 },
      { key: 'xxl', label: 'Extra Extra Large', minWidth: 1400 },
    ],
    defaultViewport: 'md',
    columnCount: 12,
    rowClasses: 'row',
    baseWidthClasses: Object.fromEntries(
      Array.from({ length: 12 }, (_, i) => [String(i + 1), `col-${i + 1}`]),
    ),
    baseOffsetClasses: Object.fromEntries(
      Array.from({ length: 12 }, (_, i) => [String(i), i === 0 ? '' : `offset-${i}`]),
    ),
  }),
}));

describe('useViewport', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('initializes with the default viewport key from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.activeViewport).toBe('md');
  });

  it('provides the list of viewports from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.viewports).toHaveLength(6);
    expect(result.current.viewports[0].key).toBe('xs');
  });

  it('updates the active viewport when setActiveViewport is called', () => {
    const { result } = renderHook(() => useViewport());

    act(() => {
      result.current.setActiveViewport('lg');
    });

    expect(result.current.activeViewport).toBe('lg');
  });

  it('provides the column count from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.columnCount).toBe(12);
  });

  it('provides row classes from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.rowClasses).toBe('row');
  });

  it('looks up base width class from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getWidthClass(6)).toBe('col-6');
  });

  it('looks up base offset class from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getOffsetClass(3)).toBe('offset-3');
  });

  it('returns empty string for zero offset', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getOffsetClass(0)).toBe('');
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/hooks/useViewport.test.ts`
Expected: FAIL — module does not exist

### Step 3: Implement the hook

Create `client/src/hooks/useViewport.ts`:

```typescript
import { useState, useMemo } from 'react';
import { getAdapterConfig } from '@/api/config';
import type { AdapterConfig, ViewportConfig } from '@/types/adapter';

export interface UseViewportReturn {
  readonly viewports: readonly ViewportConfig[];
  readonly activeViewport: string;
  readonly setActiveViewport: (key: string) => void;
  readonly columnCount: number;
  readonly rowClasses: string;
  readonly getWidthClass: (width: number) => string;
  readonly getOffsetClass: (offset: number) => string;
}

export function useViewport(): UseViewportReturn {
  const config: AdapterConfig = useMemo(() => getAdapterConfig(), []);
  const [activeViewport, setActiveViewport] = useState(config.defaultViewport);

  const getWidthClass = useMemo(
    () => (width: number) => config.baseWidthClasses[String(width)] ?? '',
    [config.baseWidthClasses],
  );

  const getOffsetClass = useMemo(
    () => (offset: number) => config.baseOffsetClasses[String(offset)] ?? '',
    [config.baseOffsetClasses],
  );

  return {
    viewports: config.viewports,
    activeViewport,
    setActiveViewport,
    columnCount: config.columnCount,
    rowClasses: config.rowClasses,
    getWidthClass,
    getOffsetClass,
  };
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/hooks/useViewport.test.ts`
Expected: PASS

### Step 5: Add export to barrel

In `client/src/hooks/index.ts`, add:

```typescript
export { useViewport } from './useViewport';
```

### Step 6: Commit

```bash
git add client/src/hooks/useViewport.ts client/src/tests/hooks/useViewport.test.ts \
  client/src/hooks/index.ts
git commit -m "feat(grid): add useViewport hook reading adapter config from CMS config"
```

---

## Task 5: Create the ViewportSwitcher component

A segmented control that renders viewport tabs and calls `setActiveViewport` on click. Uses `ViewportConfig` type from the adapter schema — no hardcoded viewport definitions.

**Files:**

- Create: `client/src/components/ViewportSwitcher/ViewportSwitcher.tsx`
- Create: `client/src/components/ViewportSwitcher/ViewportSwitcher.scss`
- Test: `client/src/tests/components/ViewportSwitcher/ViewportSwitcher.test.tsx`

### Step 1: Write the failing test

Test with viewport data passed as props (not imported from any hardcoded config). Key assertions: renders a button per viewport, marks active as `aria-pressed`, calls `onViewportChange` on click, does not call when clicking the active tab.

**Note:** Requires `@testing-library/user-event`. Install if missing: `npm install --save-dev @testing-library/user-event`.

### Step 2: Run test to verify it fails

### Step 3: Implement the component

Props: `viewports: readonly ViewportConfig[]`, `activeViewport: string`, `onViewportChange: (key: string) => void`. Pure presentational — receives everything via props, no internal state.

### Step 4: Run tests

### Step 5: Create the SCSS

Segmented control: `display: flex`, buttons with shared borders, active button uses SilverStripe primary blue (`#0071c4`), hover state on inactive buttons.

### Step 6: Import SCSS in bundle

### Step 7: Commit

```bash
git commit -m "feat(grid): add ViewportSwitcher segmented control component"
```

---

## Task 6: Create the ElementCard component

A compact read-only card displaying an element's type, title, content preview, and publication state border.

**Files:**

- Create: `client/src/components/ElementCard/ElementCard.tsx`
- Create: `client/src/components/ElementCard/ElementCard.scss`
- Test: `client/src/tests/components/ElementCard/ElementCard.test.tsx`

### Step 1: Write the failing test

Key test cases: renders title, content preview (or "No preview available" fallback), short type name (strips PHP namespace), correct status modifier class (`element-card--draft` / `--published` / `--modified`), "(untitled)" for empty title.

### Step 2-7: Standard TDD cycle (implement, SCSS, bundle import, commit)

```bash
git commit -m "feat(grid): add ElementCard component with publication state borders"
```

---

## Task 7: Create the EmptyState component

A reusable empty state placeholder for columns, rows, and the editor.

**Files:**

- Create: `client/src/components/EmptyState/EmptyState.tsx`
- Create: `client/src/components/EmptyState/EmptyState.scss`
- Test: `client/src/tests/components/EmptyState/EmptyState.test.tsx`

### Step 1: Write the failing test

Test: renders message, applies root class, applies optional `variant="centered"` modifier.

### Step 2-7: Standard TDD cycle

```bash
git commit -m "feat(grid): add EmptyState component for empty columns, rows, and editor"
```

---

## Task 8: Create the ColumnBlock component

Renders a single column with its fraction badge, applies Bootstrap base grid classes from `GridSettings` via the adapter config lookup maps passed as props, and stacks ElementCards vertically. Handles hidden columns.

**Files:**

- Create: `client/src/components/ColumnBlock/ColumnBlock.tsx`
- Create: `client/src/components/ColumnBlock/ColumnBlock.scss`
- Test: `client/src/tests/components/ColumnBlock/ColumnBlock.test.tsx`

### Step 1: Write the failing test

Key test cases:

- Renders fraction badge for active viewport (e.g., `6/12`)
- Applies width class from `getWidthClass()` prop (e.g., `col-6`)
- Applies offset class from `getOffsetClass()` prop when offset > 0
- Does not apply offset class when offset is 0
- Renders child elements as ElementCards
- Renders EmptyState when no children
- Shows hidden treatment (`column-block--hidden`) when `visible: false`
- Shows "hidden" instead of fraction badge when not visible
- Applies publication state modifier class
- Falls back to full width when viewport key is missing from gridSettings

### Step 2: Run test to verify it fails

### Step 3: Implement the component

Props: `column: ColumnNode`, `activeViewport: string`, `columnCount: number`, `getWidthClass: (w: number) => string`, `getOffsetClass: (o: number) => string`.

Reads `column.gridSettings[activeViewport]` for the current viewport's width/offset/visible. Falls back to `{ width: columnCount, offset: 0, visible: true }` if viewport key is missing. Uses the `getWidthClass` and `getOffsetClass` callbacks (from `useViewport`) to look up the correct CSS class.

Outer div gets the Bootstrap grid class. Inner div gets `column-block` BEM class with status and hidden modifiers.

### Step 4-7: Standard TDD cycle (run tests, SCSS, bundle import, commit)

```bash
git commit -m "feat(grid): add ColumnBlock component with adapter-driven grid classes and hidden state"
```

---

## Task 9: Create the RowBlock component

Renders a row using the adapter's row classes (from config), lays out ColumnBlocks horizontally.

**Files:**

- Create: `client/src/components/RowBlock/RowBlock.tsx`
- Create: `client/src/components/RowBlock/RowBlock.scss`
- Test: `client/src/tests/components/RowBlock/RowBlock.test.tsx`

### Step 1: Write the failing test

Key test cases: renders title, applies row classes from `rowClasses` prop, renders column children, EmptyState when no columns, publication state modifier.

### Step 2: Run test to verify it fails

### Step 3: Implement the component

Props: `row: RowNode`, `activeViewport: string`, `columnCount: number`, `rowClasses: string`, `getWidthClass`, `getOffsetClass`.

Uses `rowClasses` prop on the column container div. Renders ColumnBlocks inside, passing through viewport and class helpers.

### Step 4-7: Standard TDD cycle

```bash
git commit -m "feat(grid): add RowBlock component with adapter-driven row classes"
```

---

## Task 10: Create the SectionBlock component

Renders a section as the outermost container shell with its rows inside.

**Files:**

- Create: `client/src/components/SectionBlock/SectionBlock.tsx`
- Create: `client/src/components/SectionBlock/SectionBlock.scss`
- Test: `client/src/tests/components/SectionBlock/SectionBlock.test.tsx`

### Step 1: Write the failing test

Key test cases: renders title, renders row children, EmptyState when no rows, publication state modifier.

### Step 2-7: Standard TDD cycle

Props pass through all adapter-driven values to RowBlocks.

```bash
git commit -m "feat(grid): add SectionBlock component with double-line border and title"
```

---

## Task 11: Rewrite the GridEditor component to use the new component tree

Replace the proof-of-life `<ul>` tree with the composed component hierarchy: ViewportSwitcher + SectionBlocks. The GridEditor is the integration point that connects `useElementTree` (data) with `useViewport` (adapter config + state).

**Files:**

- Modify: `client/src/components/GridEditor/GridEditor.tsx` (full rewrite)
- Modify: `client/src/tests/components/GridEditor/GridEditor.test.tsx` (rewrite tests)

### Step 1: Rewrite the GridEditor component

The component:

- Calls `useElementTree(pageId)` for data
- Calls `useViewport()` for adapter config, active viewport state, and class lookup helpers
- Renders `ViewportSwitcher` with viewports from adapter config
- Flattens the tree response and filters for section nodes
- Renders `SectionBlock` for each section, passing through all adapter-driven props (`activeViewport`, `columnCount`, `rowClasses`, `getWidthClass`, `getOffsetClass`)
- Shows `EmptyState` with "No sections yet" if no sections exist
- Preserves existing loading/error states and data attributes

### Step 2: Rewrite the GridEditor tests

The test must:

- Mock `@/api/endpoints` (existing pattern)
- Mock `@/api/config` to provide `getAdapterConfig()` with Bootstrap adapter config
- Test: loading state, error state, viewport switcher renders, section blocks render, empty state renders, data attributes preserved
- Test: switching viewport updates column fraction badges (requires `userEvent`)
- Mock data needs `gridSettings` on column nodes

### Step 3: Run all tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 4: Run typecheck

Run: `npm run typecheck`
Expected: No errors

### Step 5: Update `page-lifecycle.spec.ts` selectors

The existing E2E test (`tests/E2E/specs/page-lifecycle.spec.ts`) asserts on `.grid-editor__tree` and `[data-element-id]` — both from the proof-of-life `<ul>` tree. Update selectors to match the new component hierarchy:

- `.grid-editor__tree` → `.section-block` (check that at least one section rendered)
- `[data-element-id]` containing "Main Section" → `.section-block` containing "Main Section"
- `.grid-editor__loading` remains unchanged (still rendered by GridEditor during data fetch)

### Step 6: Commit

```bash
git add client/src/components/GridEditor/GridEditor.tsx \
  client/src/tests/components/GridEditor/GridEditor.test.tsx \
  tests/E2E/specs/page-lifecycle.spec.ts
git commit -m "feat(grid): replace proof-of-life tree with visual Layered Blocks grid editor"
```

---

## Task 12: E2E test specs for grid editor features

Define and implement Playwright E2E tests for the viewport switcher, publication state indicators, grid layout rendering, and empty states. These tests exercise the full stack — PHP backend serving real data through to the React frontend rendering it.

**Fixtures used:**

- `element-tree` — full hierarchy with varied `GridSettings` per viewport (updated in Task 1)
- `complex-page` — three publication states: draft, published, modified (existing post-actions)
- `empty-page` — bare page with no elements

**Files:**

- Create: `tests/E2E/specs/grid-editor.spec.ts`

### Step 1: Create the E2E spec file

Create `tests/E2E/specs/grid-editor.spec.ts` with the following user story test groups:

```typescript
import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

// ────────────────────────────────────────────────────────────────
// Grid layout rendering
// ────────────────────────────────────────────────────────────────

test.describe('Grid editor — layout rendering', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('editor sees sections, rows, columns, and element cards', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Section rendered
    await expect(page.locator('.section-block')).toHaveCount(1);
    await expect(
      page.locator('.section-block', { hasText: 'Main Section' }),
    ).toBeVisible();

    // Row within section
    await expect(page.locator('.row-block')).toHaveCount(1);

    // Columns within row
    await expect(page.locator('.column-block')).toHaveCount(2);

    // Element cards within columns
    await expect(page.locator('.element-card')).toHaveCount(3);
    await expect(page.locator('.element-card', { hasText: 'Text Block' })).toBeVisible();
    await expect(page.locator('.element-card', { hasText: 'Image Block' })).toBeVisible();
    await expect(page.locator('.element-card', { hasText: 'Video Block' })).toBeVisible();
  });

  test('columns display fraction badges matching their grid settings', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Default viewport is md: col1=8/12, col2=4/12
    const fractions = page.locator('.column-block__fraction');
    await expect(fractions.first()).toContainText('8/12');
    await expect(fractions.nth(1)).toContainText('4/12');
  });
});

// ────────────────────────────────────────────────────────────────
// Viewport switcher
// ────────────────────────────────────────────────────────────────

test.describe('Grid editor — viewport switcher', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('renders a tab for each adapter viewport with default selected', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    const switcher = page.locator('.viewport-switcher');
    await expect(switcher).toBeVisible();

    // Bootstrap adapter: 6 viewports (xs, sm, md, lg, xl, xxl)
    const buttons = switcher.locator('button');
    await expect(buttons).toHaveCount(6);

    // Default viewport (md) is active
    const mdButton = buttons.filter({ hasText: 'Medium' });
    await expect(mdButton).toHaveAttribute('aria-pressed', 'true');
  });

  test('switching viewport updates fraction badges', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    const fractions = page.locator('.column-block__fraction');

    // md (default): col1=8/12, col2=4/12
    await expect(fractions.first()).toContainText('8/12');
    await expect(fractions.nth(1)).toContainText('4/12');

    // Switch to lg: both columns 6/12
    await page.locator('.viewport-switcher button', { hasText: 'Large' }).click();
    await expect(fractions.first()).toContainText('6/12');
    await expect(fractions.nth(1)).toContainText('6/12');
  });

  test('switching to viewport where column is hidden shows hidden indicator', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'element-tree');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Switch to xs: col2 is visible=false
    await page
      .locator('.viewport-switcher button', { hasText: 'Extra Small' })
      .click();

    const col2 = page.locator('.column-block').nth(1);
    await expect(col2).toHaveClass(/column-block--hidden/);

    // Fraction badge shows "hidden" instead of a width ratio
    await expect(
      col2.locator('.column-block__fraction'),
    ).toContainText('hidden');
  });
});

// ────────────────────────────────────────────────────────────────
// Publication state indicators
// ────────────────────────────────────────────────────────────────

test.describe('Grid editor — publication state', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('element cards show correct status modifier per publication state', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'complex-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Draft element (unpublished after page-level publishRecursive)
    const draftCard = page.locator('.element-card', {
      hasText: 'Draft Only Block',
    });
    await expect(draftCard).toHaveClass(/element-card--draft/);

    // Published element (remains on both Draft and Live)
    const publishedCard = page.locator('.element-card', {
      hasText: 'Published Block',
    });
    await expect(publishedCard).toHaveClass(/element-card--published/);

    // Modified element (published, then draft title changed)
    const modifiedCard = page.locator('.element-card', {
      hasText: 'Modified Text Block',
    });
    await expect(modifiedCard).toHaveClass(/element-card--modified/);
  });

  test('status borders appear at section, row, column, and element levels', async ({
    page,
  }) => {
    const fixture = await loadFixture(page.request, 'complex-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    // Each hierarchy level should have a status modifier class
    await expect(
      page.locator('[class*="section-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="row-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="column-block--"]').first(),
    ).toBeVisible();
    await expect(
      page.locator('[class*="element-card--"]').first(),
    ).toBeVisible();
  });
});

// ────────────────────────────────────────────────────────────────
// Empty states
// ────────────────────────────────────────────────────────────────

test.describe('Grid editor — empty states', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('empty page shows "No sections yet" message', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'empty-page');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(
      page.locator('.grid-editor__loading'),
    ).toBeHidden({ timeout: 15_000 });

    await expect(page.getByText('No sections yet')).toBeVisible();
  });
});
```

### Step 2: Run the E2E tests (requires Docker services running)

Run: `npm run test:e2e -- --grep "Grid editor"`
Expected: ALL PASS (assuming Docker services and fixture endpoints are available)

### Step 3: Commit

```bash
git add tests/E2E/specs/grid-editor.spec.ts
git commit -m "test(e2e): add grid editor specs for layout, viewport switcher, status, and empty states"
```

---

## Task 13: Final QA and build verification

Run the full QA suite to verify everything works together.

### Step 1: Run all frontend tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 2: Run typecheck

Run: `npm run typecheck`
Expected: No errors

### Step 3: Run production build

Run: `npm run build`
Expected: Vite builds successfully, outputs `client/dist/js/bundle.js` and `client/dist/styles/bundle.css`

### Step 4: Run PHP tests (if Docker is available)

Run: `make test-unit`
Expected: ALL PASS (including new ElementNode gridSettings + controller getClientConfig tests)

### Step 5: Commit any remaining fixes

If any issues were found and fixed, commit them.

### Step 6: Verify clean working tree

```bash
git status
```
