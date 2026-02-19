# Grid Editor Frontend Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the proof-of-life `<ul>` tree with a read-only visual grid editor using the "Layered Blocks" design — sections, rows, columns rendered as a real Bootstrap grid with publication state indicators, a viewport switcher, and fraction badges.

**Architecture:** The GridEditor component is decomposed into small, single-responsibility components (ViewportSwitcher, SectionBlock, RowBlock, ColumnBlock, ElementCard, EmptyState). Grid layout uses actual Bootstrap CSS classes generated for the selected viewport's column widths. A `useViewport` hook manages the active viewport state. Adapter config (viewports, column count) is hardcoded for Bootstrap in this iteration — a future API endpoint will serve it dynamically.

**Tech Stack:** React 18, TypeScript 5.9, Vitest + React Testing Library, SCSS (BEM), Bootstrap 4 grid classes (from SS admin), Zod validation

**Design doc:** `docs/plans/2026-02-19-grid-editor-frontend-design.md`

---

## Task 1: Add `gridSettings` to the column Zod schema and TypeScript types

The `readTree` API response needs to include `gridSettings` on column nodes. The backend `ElementNode` DTO must be updated, and the frontend Zod schema must expect it.

**Files:**
- Modify: `src/Model/ElementNode.php:15-31` (PHPStan type), `src/Model/ElementNode.php:41-57` (constructor + serialize)
- Modify: `src/Service/ElementTreeBuilder.php:132-170` (pass gridSettings to node)
- Modify: `client/src/types/elements.ts:42-46` (columnNodeSchema)
- Test: `client/src/tests/types/elements.test.ts`
- Test: `tests/Unit/Model/ElementNodeTest.php` (if exists, or create)

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

Add an optional `gridSettings` parameter to the constructor. Include it in `jsonSerialize()` only for column containers.

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
Expected: FAIL — `gridSettings` is not defined in the schema (Zod strips unknown keys or fails)

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

Export the inferred type:
```typescript
export type GridSettings = z.infer<typeof gridSettingsSchema>;
export type ViewportSettings = z.infer<typeof viewportSettingsSchema>;
```

### Step 9: Run frontend tests to verify they pass

Run: `npm run test -- --run`
Expected: PASS (all tests, including existing ones — but the existing `GridEditor.test.tsx` mock data will need `gridSettings` added to column nodes)

### Step 10: Update test fixtures

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

### Step 11: Run all tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 12: Commit

```bash
git add src/Model/ElementNode.php src/Service/ElementTreeBuilder.php \
  client/src/types/elements.ts \
  client/src/tests/types/elements.test.ts \
  client/src/tests/components/GridEditor/GridEditor.test.tsx \
  tests/Unit/Model/ElementNodeTest.php
git commit -m "feat(grid): include gridSettings in column node API response and Zod schema"
```

---

## Task 2: Create Bootstrap adapter config for the frontend

The frontend needs viewport definitions and a way to generate base CSS classes. For this demo, hardcode the Bootstrap adapter config as a TypeScript module. Future iterations will fetch this from an API endpoint.

**Files:**
- Create: `client/src/config/bootstrapAdapter.ts`
- Test: `client/src/tests/config/bootstrapAdapter.test.ts`

### Step 1: Write the failing test

```typescript
import {
  VIEWPORTS,
  DEFAULT_VIEWPORT_KEY,
  COLUMN_COUNT,
  getBaseWidthClass,
  getBaseOffsetClass,
} from '@/config/bootstrapAdapter';

describe('bootstrapAdapter', () => {
  describe('VIEWPORTS', () => {
    it('has 6 viewports in order', () => {
      expect(VIEWPORTS).toHaveLength(6);
      expect(VIEWPORTS.map((v) => v.key)).toEqual(['xs', 'sm', 'md', 'lg', 'xl', 'xxl']);
    });
  });

  describe('DEFAULT_VIEWPORT_KEY', () => {
    it('defaults to md', () => {
      expect(DEFAULT_VIEWPORT_KEY).toBe('md');
    });
  });

  describe('COLUMN_COUNT', () => {
    it('is 12', () => {
      expect(COLUMN_COUNT).toBe(12);
    });
  });

  describe('getBaseWidthClass', () => {
    it('returns col-{n} for xs viewport', () => {
      expect(getBaseWidthClass(6)).toBe('col-6');
    });

    it('returns col-{n} for any width', () => {
      expect(getBaseWidthClass(12)).toBe('col-12');
      expect(getBaseWidthClass(1)).toBe('col-1');
    });
  });

  describe('getBaseOffsetClass', () => {
    it('returns offset-{n} for non-zero offset', () => {
      expect(getBaseOffsetClass(3)).toBe('offset-3');
    });

    it('returns empty string for zero offset', () => {
      expect(getBaseOffsetClass(0)).toBe('');
    });
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/config/bootstrapAdapter.test.ts`
Expected: FAIL — module does not exist

### Step 3: Implement the adapter config

Create `client/src/config/bootstrapAdapter.ts`:

```typescript
export interface ViewportConfig {
  readonly key: string;
  readonly label: string;
  readonly minWidth: number | null;
}

export const VIEWPORTS: readonly ViewportConfig[] = [
  { key: 'xs', label: 'Extra Small', minWidth: null },
  { key: 'sm', label: 'Small', minWidth: 576 },
  { key: 'md', label: 'Medium', minWidth: 768 },
  { key: 'lg', label: 'Large', minWidth: 992 },
  { key: 'xl', label: 'Extra Large', minWidth: 1200 },
  { key: 'xxl', label: 'Extra Extra Large', minWidth: 1400 },
] as const;

export const DEFAULT_VIEWPORT_KEY = 'md';

export const COLUMN_COUNT = 12;

/**
 * Base Bootstrap width class — always applies regardless of viewport.
 * The viewport switcher controls which GridSettings values feed into this.
 */
export function getBaseWidthClass(width: number): string {
  return `col-${width}`;
}

/**
 * Base Bootstrap offset class. Returns empty string for zero offset.
 */
export function getBaseOffsetClass(offset: number): string {
  if (offset === 0) {
    return '';
  }

  return `offset-${offset}`;
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/config/bootstrapAdapter.test.ts`
Expected: PASS

### Step 5: Commit

```bash
git add client/src/config/bootstrapAdapter.ts client/src/tests/config/bootstrapAdapter.test.ts
git commit -m "feat(grid): add Bootstrap adapter config for frontend viewport and class generation"
```

---

## Task 3: Create the `useViewport` hook

A hook that manages the currently selected viewport key and provides the list of available viewports. Components use this to determine which `GridSettings` values to read.

**Files:**
- Create: `client/src/hooks/useViewport.ts`
- Test: `client/src/tests/hooks/useViewport.test.ts`
- Modify: `client/src/hooks/index.ts` (add export)

### Step 1: Write the failing test

```typescript
import { renderHook, act } from '@testing-library/react';
import { useViewport } from '@/hooks/useViewport';

describe('useViewport', () => {
  it('initializes with the default viewport key', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.activeViewport).toBe('md');
  });

  it('provides the list of viewports', () => {
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

  it('provides the column count', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.columnCount).toBe(12);
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/hooks/useViewport.test.ts`
Expected: FAIL — module does not exist

### Step 3: Implement the hook

Create `client/src/hooks/useViewport.ts`:

```typescript
import { useState } from 'react';
import {
  VIEWPORTS,
  DEFAULT_VIEWPORT_KEY,
  COLUMN_COUNT,
  type ViewportConfig,
} from '@/config/bootstrapAdapter';

export interface UseViewportReturn {
  readonly viewports: readonly ViewportConfig[];
  readonly activeViewport: string;
  readonly setActiveViewport: (key: string) => void;
  readonly columnCount: number;
}

export function useViewport(): UseViewportReturn {
  const [activeViewport, setActiveViewport] = useState(DEFAULT_VIEWPORT_KEY);

  return {
    viewports: VIEWPORTS,
    activeViewport,
    setActiveViewport,
    columnCount: COLUMN_COUNT,
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
git add client/src/hooks/useViewport.ts client/src/tests/hooks/useViewport.test.ts client/src/hooks/index.ts
git commit -m "feat(grid): add useViewport hook for viewport state management"
```

---

## Task 4: Create the ViewportSwitcher component

A segmented control that renders viewport tabs and calls `setActiveViewport` on click.

**Files:**
- Create: `client/src/components/ViewportSwitcher/ViewportSwitcher.tsx`
- Create: `client/src/components/ViewportSwitcher/ViewportSwitcher.scss`
- Test: `client/src/tests/components/ViewportSwitcher/ViewportSwitcher.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import { VIEWPORTS } from '@/config/bootstrapAdapter';

describe('ViewportSwitcher', () => {
  const defaultProps = {
    viewports: VIEWPORTS,
    activeViewport: 'md',
    onViewportChange: vi.fn(),
  };

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders a button for each viewport', () => {
    render(<ViewportSwitcher {...defaultProps} />);

    for (const vp of VIEWPORTS) {
      expect(screen.getByRole('button', { name: vp.label })).toBeDefined();
    }
  });

  it('marks the active viewport button as pressed', () => {
    render(<ViewportSwitcher {...defaultProps} />);

    const activeButton = screen.getByRole('button', { name: 'Medium' });
    expect(activeButton.getAttribute('aria-pressed')).toBe('true');
  });

  it('marks non-active viewport buttons as not pressed', () => {
    render(<ViewportSwitcher {...defaultProps} />);

    const inactiveButton = screen.getByRole('button', { name: 'Large' });
    expect(inactiveButton.getAttribute('aria-pressed')).toBe('false');
  });

  it('calls onViewportChange when a viewport button is clicked', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();
    render(<ViewportSwitcher {...defaultProps} onViewportChange={onChange} />);

    await user.click(screen.getByRole('button', { name: 'Large' }));

    expect(onChange).toHaveBeenCalledWith('lg');
  });

  it('does not call onViewportChange when active viewport is clicked', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();
    render(<ViewportSwitcher {...defaultProps} onViewportChange={onChange} />);

    await user.click(screen.getByRole('button', { name: 'Medium' }));

    expect(onChange).not.toHaveBeenCalled();
  });
});
```

**Note:** This test requires `@testing-library/user-event`. Check if it's already a dependency; if not, install it: `npm install --save-dev @testing-library/user-event`.

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/ViewportSwitcher/ViewportSwitcher.test.tsx`
Expected: FAIL — module does not exist

### Step 3: Implement the component

Create `client/src/components/ViewportSwitcher/ViewportSwitcher.tsx`:

```tsx
import type { ViewportConfig } from '@/config/bootstrapAdapter';

interface ViewportSwitcherProps {
  readonly viewports: readonly ViewportConfig[];
  readonly activeViewport: string;
  readonly onViewportChange: (key: string) => void;
}

export default function ViewportSwitcher({
  viewports,
  activeViewport,
  onViewportChange,
}: ViewportSwitcherProps) {
  return (
    <div className="viewport-switcher" role="group" aria-label="Viewport">
      {viewports.map((vp) => {
        const isActive = vp.key === activeViewport;
        return (
          <button
            key={vp.key}
            type="button"
            className={`viewport-switcher__button${isActive ? ' viewport-switcher__button--active' : ''}`}
            aria-pressed={isActive}
            onClick={() => {
              if (!isActive) {
                onViewportChange(vp.key);
              }
            }}
          >
            {vp.label}
          </button>
        );
      })}
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/ViewportSwitcher/ViewportSwitcher.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/ViewportSwitcher/ViewportSwitcher.scss`:

```scss
.viewport-switcher {
  display: flex;
  gap: 0;
  margin-bottom: 16px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  overflow: hidden;
  width: fit-content;
}

.viewport-switcher__button {
  padding: 6px 14px;
  border: none;
  border-right: 1px solid #dee2e6;
  background: #f8f9fa;
  color: #495057;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;

  &:last-child {
    border-right: none;
  }

  &:hover:not(.viewport-switcher__button--active) {
    background: #e9ecef;
  }

  &--active {
    background: #0071c4;
    color: #fff;
    cursor: default;
  }
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/ViewportSwitcher/ViewportSwitcher';
```

### Step 7: Commit

```bash
git add client/src/components/ViewportSwitcher/ \
  client/src/tests/components/ViewportSwitcher/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add ViewportSwitcher segmented control component"
```

---

## Task 5: Create the ElementCard component

A compact read-only card displaying an element's type icon, title, content preview, and publication state border.

**Files:**
- Create: `client/src/components/ElementCard/ElementCard.tsx`
- Create: `client/src/components/ElementCard/ElementCard.scss`
- Test: `client/src/tests/components/ElementCard/ElementCard.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import ElementCard from '@/components/ElementCard/ElementCard';
import type { SimpleElementNode } from '@/types/elements';

const baseElement: SimpleElementNode = {
  id: 10,
  title: 'Welcome Text',
  blockSchema: {
    typeName: 'App\\Blocks\\TextBlock',
    actions: { edit: '/edit/10' },
    content: 'Welcome to our site',
  },
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

describe('ElementCard', () => {
  it('renders the element title', () => {
    render(<ElementCard element={baseElement} />);
    expect(screen.getByText('Welcome Text')).toBeDefined();
  });

  it('renders the content preview', () => {
    render(<ElementCard element={baseElement} />);
    expect(screen.getByText('Welcome to our site')).toBeDefined();
  });

  it('shows fallback text when content is empty', () => {
    const emptyElement = {
      ...baseElement,
      blockSchema: { ...baseElement.blockSchema, content: '' },
    };
    render(<ElementCard element={emptyElement} />);
    expect(screen.getByText('No preview available')).toBeDefined();
  });

  it('renders the short type name without namespace', () => {
    render(<ElementCard element={baseElement} />);
    expect(screen.getByText('TextBlock')).toBeDefined();
  });

  it('applies draft status modifier class', () => {
    const { container } = render(<ElementCard element={baseElement} />);
    expect(container.querySelector('.element-card--draft')).not.toBeNull();
  });

  it('applies published status modifier class', () => {
    const publishedElement = { ...baseElement, isPublished: true, isLiveVersion: true };
    const { container } = render(<ElementCard element={publishedElement} />);
    expect(container.querySelector('.element-card--published')).not.toBeNull();
  });

  it('applies modified status modifier class', () => {
    const modifiedElement = { ...baseElement, isPublished: true, isLiveVersion: false };
    const { container } = render(<ElementCard element={modifiedElement} />);
    expect(container.querySelector('.element-card--modified')).not.toBeNull();
  });

  it('shows (untitled) when title is empty', () => {
    const untitled = { ...baseElement, title: '' };
    render(<ElementCard element={untitled} />);
    expect(screen.getByText('(untitled)')).toBeDefined();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/ElementCard/ElementCard.test.tsx`
Expected: FAIL — module does not exist

### Step 3: Implement the component

Create `client/src/components/ElementCard/ElementCard.tsx`:

```tsx
import type { SimpleElementNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';

interface ElementCardProps {
  readonly element: SimpleElementNode;
}

/**
 * Extracts the short class name from a fully qualified name.
 * "App\\Blocks\\TextBlock" → "TextBlock"
 */
function shortTypeName(fqcn: string): string {
  const parts = fqcn.split('\\');
  return parts[parts.length - 1];
}

export default function ElementCard({ element }: ElementCardProps) {
  const status = deriveElementStatus(element.isPublished, element.isLiveVersion);
  const title = element.title || '(untitled)';
  const preview = element.blockSchema.content || '';

  return (
    <div className={`element-card element-card--${status}`} data-element-id={element.id}>
      <div className="element-card__header">
        <span className="element-card__type">{shortTypeName(element.blockSchema.typeName)}</span>
        <span className="element-card__title">{title}</span>
      </div>
      <div className="element-card__preview">
        {preview !== ''
          ? preview
          : <span className="element-card__no-preview">No preview available</span>}
      </div>
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/ElementCard/ElementCard.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/ElementCard/ElementCard.scss`:

```scss
.element-card {
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  padding: 8px 10px;
  border-left: 3px solid transparent;

  &--draft {
    border-left-color: #0071c4;
  }

  &--published {
    border-left-color: #3fa142;
  }

  &--modified {
    border-left-color: #d4a017;
  }
}

.element-card__header {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 2px;
}

.element-card__type {
  font-size: 11px;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  flex-shrink: 0;
}

.element-card__title {
  font-size: 13px;
  font-weight: 600;
  color: #212529;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.element-card__preview {
  font-size: 12px;
  color: #6c757d;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.element-card__no-preview {
  font-style: italic;
  color: #adb5bd;
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/ElementCard/ElementCard';
```

### Step 7: Commit

```bash
git add client/src/components/ElementCard/ \
  client/src/tests/components/ElementCard/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add ElementCard component with publication state borders"
```

---

## Task 6: Create the EmptyState component

A reusable empty state placeholder for columns with no elements, rows with no columns, and the editor with no sections.

**Files:**
- Create: `client/src/components/EmptyState/EmptyState.tsx`
- Create: `client/src/components/EmptyState/EmptyState.scss`
- Test: `client/src/tests/components/EmptyState/EmptyState.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import EmptyState from '@/components/EmptyState/EmptyState';

describe('EmptyState', () => {
  it('renders the provided message', () => {
    render(<EmptyState message="No content blocks" />);
    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('applies the root CSS class', () => {
    const { container } = render(<EmptyState message="Empty" />);
    expect(container.querySelector('.empty-state')).not.toBeNull();
  });

  it('applies a variant modifier when provided', () => {
    const { container } = render(<EmptyState message="No sections yet" variant="centered" />);
    expect(container.querySelector('.empty-state--centered')).not.toBeNull();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/EmptyState/EmptyState.test.tsx`
Expected: FAIL

### Step 3: Implement the component

Create `client/src/components/EmptyState/EmptyState.tsx`:

```tsx
interface EmptyStateProps {
  readonly message: string;
  readonly variant?: 'centered';
}

export default function EmptyState({ message, variant }: EmptyStateProps) {
  const className = `empty-state${variant !== undefined ? ` empty-state--${variant}` : ''}`;

  return (
    <div className={className}>
      <span className="empty-state__message">{message}</span>
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/EmptyState/EmptyState.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/EmptyState/EmptyState.scss`:

```scss
.empty-state {
  border: 2px dashed #dee2e6;
  border-radius: 4px;
  padding: 16px;
  text-align: center;

  &--centered {
    padding: 48px 16px;
  }
}

.empty-state__message {
  font-size: 13px;
  color: #adb5bd;
  font-style: italic;
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/EmptyState/EmptyState';
```

### Step 7: Commit

```bash
git add client/src/components/EmptyState/ \
  client/src/tests/components/EmptyState/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add EmptyState component for empty columns, rows, and editor"
```

---

## Task 7: Create the ColumnBlock component

Renders a single column with its fraction badge, applies Bootstrap base grid classes from `GridSettings`, and stacks ElementCards vertically. Handles hidden columns.

**Files:**
- Create: `client/src/components/ColumnBlock/ColumnBlock.tsx`
- Create: `client/src/components/ColumnBlock/ColumnBlock.scss`
- Test: `client/src/tests/components/ColumnBlock/ColumnBlock.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import type { ColumnNode } from '@/types/elements';

const baseColumn: ColumnNode = {
  id: 3,
  title: 'Left Column',
  containerType: 'column',
  allowedTypes: null,
  children: [
    {
      id: 10,
      title: 'Text Block',
      blockSchema: { typeName: 'App\\Blocks\\TextBlock', actions: { edit: '/edit/10' }, content: 'Hello world' },
      obsoleteClassName: null,
      version: 1,
      isPublished: true,
      isLiveVersion: true,
      canDelete: true,
      canPublish: true,
      canUnpublish: true,
      canCreate: true,
      statusFlags: {},
    },
  ],
  gridSettings: {
    xs: { width: 12, offset: 0, visible: true },
    md: { width: 6, offset: 0, visible: true },
    lg: { width: 4, offset: 2, visible: true },
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

describe('ColumnBlock', () => {
  it('renders the fraction badge for the active viewport', () => {
    render(<ColumnBlock column={baseColumn} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('6/12')).toBeDefined();
  });

  it('applies Bootstrap base width class', () => {
    const { container } = render(<ColumnBlock column={baseColumn} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.col-6')).not.toBeNull();
  });

  it('applies Bootstrap base offset class when offset > 0', () => {
    const { container } = render(<ColumnBlock column={baseColumn} activeViewport="lg" columnCount={12} />);
    expect(container.querySelector('.offset-2')).not.toBeNull();
  });

  it('does not apply offset class when offset is 0', () => {
    const { container } = render(<ColumnBlock column={baseColumn} activeViewport="md" columnCount={12} />);
    const col = container.querySelector('.col-6');
    expect(col?.classList.contains('offset-0')).toBe(false);
  });

  it('renders child elements', () => {
    render(<ColumnBlock column={baseColumn} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('Text Block')).toBeDefined();
  });

  it('renders empty state when no children', () => {
    const emptyCol = { ...baseColumn, children: null };
    render(<ColumnBlock column={emptyCol} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('No content blocks')).toBeDefined();
  });

  it('shows hidden treatment when not visible at active viewport', () => {
    const hiddenCol = {
      ...baseColumn,
      gridSettings: {
        ...baseColumn.gridSettings,
        md: { width: 6, offset: 0, visible: false },
      },
    };
    const { container } = render(<ColumnBlock column={hiddenCol} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.column-block--hidden')).not.toBeNull();
  });

  it('shows "hidden" instead of fraction when not visible', () => {
    const hiddenCol = {
      ...baseColumn,
      gridSettings: {
        ...baseColumn.gridSettings,
        md: { width: 6, offset: 0, visible: false },
      },
    };
    render(<ColumnBlock column={hiddenCol} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('hidden')).toBeDefined();
  });

  it('applies publication state modifier', () => {
    const { container } = render(<ColumnBlock column={baseColumn} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.column-block--draft')).not.toBeNull();
  });

  it('falls back to full width when viewport key is missing from gridSettings', () => {
    const { container } = render(<ColumnBlock column={baseColumn} activeViewport="xxl" columnCount={12} />);
    expect(container.querySelector('.col-12')).not.toBeNull();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/ColumnBlock/ColumnBlock.test.tsx`
Expected: FAIL

### Step 3: Implement the component

Create `client/src/components/ColumnBlock/ColumnBlock.tsx`:

```tsx
import type { ColumnNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import { getBaseWidthClass, getBaseOffsetClass } from '@/config/bootstrapAdapter';
import ElementCard from '@/components/ElementCard/ElementCard';
import EmptyState from '@/components/EmptyState/EmptyState';

interface ColumnBlockProps {
  readonly column: ColumnNode;
  readonly activeViewport: string;
  readonly columnCount: number;
}

const DEFAULT_SETTINGS = { width: 12, offset: 0, visible: true } as const;

export default function ColumnBlock({ column, activeViewport, columnCount }: ColumnBlockProps) {
  const settings = column.gridSettings[activeViewport] ?? DEFAULT_SETTINGS;
  const status = deriveElementStatus(column.isPublished, column.isLiveVersion);
  const isHidden = !settings.visible;

  const gridClasses = [
    getBaseWidthClass(settings.width),
    getBaseOffsetClass(settings.offset),
  ].filter(Boolean).join(' ');

  const blockClasses = [
    'column-block',
    `column-block--${status}`,
    isHidden ? 'column-block--hidden' : '',
  ].filter(Boolean).join(' ');

  return (
    <div className={`${gridClasses}`} data-element-id={column.id}>
      <div className={blockClasses}>
        <div className="column-block__header">
          <span className="column-block__badge">
            {isHidden ? 'hidden' : `${settings.width}/${columnCount}`}
          </span>
        </div>
        <div className="column-block__content">
          {column.children !== null && column.children.length > 0
            ? column.children.map((child) => (
                <ElementCard key={child.id} element={child} />
              ))
            : <EmptyState message="No content blocks" />}
        </div>
      </div>
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/ColumnBlock/ColumnBlock.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/ColumnBlock/ColumnBlock.scss`:

```scss
.column-block {
  background: #f8f9fa;
  border-radius: 4px;
  padding: 8px;
  min-height: 60px;
  border-left: 3px solid transparent;

  &--draft {
    border-left-color: #0071c4;
  }

  &--published {
    border-left-color: #3fa142;
  }

  &--modified {
    border-left-color: #d4a017;
  }

  &--hidden {
    opacity: 0.4;
    position: relative;

    &::after {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: 4px;
      pointer-events: none;
      background: repeating-linear-gradient(
        -45deg,
        transparent,
        transparent 4px,
        rgba(0, 0, 0, 0.04) 4px,
        rgba(0, 0, 0, 0.04) 8px
      );
    }
  }
}

.column-block__header {
  margin-bottom: 6px;
}

.column-block__badge {
  display: inline-block;
  padding: 1px 6px;
  font-size: 11px;
  font-weight: 600;
  color: #6c757d;
  background: #e9ecef;
  border-radius: 3px;
}

.column-block__content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/ColumnBlock/ColumnBlock';
```

### Step 7: Commit

```bash
git add client/src/components/ColumnBlock/ \
  client/src/tests/components/ColumnBlock/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add ColumnBlock component with Bootstrap grid classes and hidden state"
```

---

## Task 8: Create the RowBlock component

Renders a row using the adapter's row classes (Bootstrap `row`), lays out ColumnBlocks horizontally.

**Files:**
- Create: `client/src/components/RowBlock/RowBlock.tsx`
- Create: `client/src/components/RowBlock/RowBlock.scss`
- Test: `client/src/tests/components/RowBlock/RowBlock.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import RowBlock from '@/components/RowBlock/RowBlock';
import type { RowNode } from '@/types/elements';

const baseRow: RowNode = {
  id: 2,
  title: 'First Row',
  containerType: 'row',
  allowedTypes: null,
  children: [
    {
      id: 3,
      title: 'Left Column',
      containerType: 'column',
      allowedTypes: null,
      children: [],
      gridSettings: {
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
    },
  ],
  blockSchema: { typeName: 'Row', actions: { edit: '/edit/2' }, content: '' },
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

describe('RowBlock', () => {
  it('renders the row title', () => {
    render(<RowBlock row={baseRow} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('First Row')).toBeDefined();
  });

  it('applies the Bootstrap row class', () => {
    const { container } = render(<RowBlock row={baseRow} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.row')).not.toBeNull();
  });

  it('renders column children', () => {
    render(<RowBlock row={baseRow} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('6/12')).toBeDefined();
  });

  it('renders empty state when no columns', () => {
    const emptyRow = { ...baseRow, children: null };
    render(<RowBlock row={emptyRow} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('No columns')).toBeDefined();
  });

  it('applies publication state modifier', () => {
    const { container } = render(<RowBlock row={baseRow} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.row-block--draft')).not.toBeNull();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/RowBlock/RowBlock.test.tsx`
Expected: FAIL

### Step 3: Implement the component

Create `client/src/components/RowBlock/RowBlock.tsx`:

```tsx
import type { RowNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface RowBlockProps {
  readonly row: RowNode;
  readonly activeViewport: string;
  readonly columnCount: number;
}

export default function RowBlock({ row, activeViewport, columnCount }: RowBlockProps) {
  const status = deriveElementStatus(row.isPublished, row.isLiveVersion);

  return (
    <div className={`row-block row-block--${status}`} data-element-id={row.id}>
      <div className="row-block__label">{row.title || '(untitled)'}</div>
      <div className="row">
        {row.children !== null && row.children.length > 0
          ? row.children.map((col) => (
              <ColumnBlock
                key={col.id}
                column={col}
                activeViewport={activeViewport}
                columnCount={columnCount}
              />
            ))
          : <div className="col-12"><EmptyState message="No columns" /></div>}
      </div>
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/RowBlock/RowBlock.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/RowBlock/RowBlock.scss`:

```scss
.row-block {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 10px;
  border-left: 3px solid transparent;

  &--draft {
    border-left-color: #0071c4;
  }

  &--published {
    border-left-color: #3fa142;
  }

  &--modified {
    border-left-color: #d4a017;
  }

  & + & {
    margin-top: 12px;
  }
}

.row-block__label {
  font-size: 12px;
  font-weight: 500;
  color: #6c757d;
  margin-bottom: 8px;
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/RowBlock/RowBlock';
```

### Step 7: Commit

```bash
git add client/src/components/RowBlock/ \
  client/src/tests/components/RowBlock/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add RowBlock component with Bootstrap row class"
```

---

## Task 9: Create the SectionBlock component

Renders a section as the outermost container shell with its rows inside.

**Files:**
- Create: `client/src/components/SectionBlock/SectionBlock.tsx`
- Create: `client/src/components/SectionBlock/SectionBlock.scss`
- Test: `client/src/tests/components/SectionBlock/SectionBlock.test.tsx`

### Step 1: Write the failing test

```typescript
import { render, screen } from '@testing-library/react';
import SectionBlock from '@/components/SectionBlock/SectionBlock';
import type { SectionNode } from '@/types/elements';

const baseSection: SectionNode = {
  id: 1,
  title: 'Main Section',
  containerType: 'section',
  allowedTypes: null,
  children: [
    {
      id: 2,
      title: 'First Row',
      containerType: 'row',
      allowedTypes: null,
      children: [],
      blockSchema: { typeName: 'Row', actions: { edit: '/edit/2' }, content: '' },
      obsoleteClassName: null,
      version: 1,
      isPublished: false,
      isLiveVersion: false,
      canDelete: true,
      canPublish: true,
      canUnpublish: false,
      canCreate: true,
      statusFlags: {},
    },
  ],
  blockSchema: { typeName: 'Section', actions: { edit: '/edit/1' }, content: '' },
  obsoleteClassName: null,
  version: 1,
  isPublished: true,
  isLiveVersion: true,
  canDelete: true,
  canPublish: true,
  canUnpublish: true,
  canCreate: true,
  statusFlags: {},
};

describe('SectionBlock', () => {
  it('renders the section title', () => {
    render(<SectionBlock section={baseSection} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('Main Section')).toBeDefined();
  });

  it('renders row children', () => {
    render(<SectionBlock section={baseSection} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('First Row')).toBeDefined();
  });

  it('renders empty state when no rows', () => {
    const emptySection = { ...baseSection, children: null };
    render(<SectionBlock section={emptySection} activeViewport="md" columnCount={12} />);
    expect(screen.getByText('No rows')).toBeDefined();
  });

  it('applies publication state modifier', () => {
    const { container } = render(<SectionBlock section={baseSection} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.section-block--published')).not.toBeNull();
  });

  it('applies the section-block root class', () => {
    const { container } = render(<SectionBlock section={baseSection} activeViewport="md" columnCount={12} />);
    expect(container.querySelector('.section-block')).not.toBeNull();
  });
});
```

### Step 2: Run test to verify it fails

Run: `npm run test -- --run client/src/tests/components/SectionBlock/SectionBlock.test.tsx`
Expected: FAIL

### Step 3: Implement the component

Create `client/src/components/SectionBlock/SectionBlock.tsx`:

```tsx
import type { SectionNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import RowBlock from '@/components/RowBlock/RowBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface SectionBlockProps {
  readonly section: SectionNode;
  readonly activeViewport: string;
  readonly columnCount: number;
}

export default function SectionBlock({ section, activeViewport, columnCount }: SectionBlockProps) {
  const status = deriveElementStatus(section.isPublished, section.isLiveVersion);

  return (
    <div className={`section-block section-block--${status}`} data-element-id={section.id}>
      <div className="section-block__title">{section.title || '(untitled)'}</div>
      <div className="section-block__body">
        {section.children !== null && section.children.length > 0
          ? section.children.map((row) => (
              <RowBlock
                key={row.id}
                row={row}
                activeViewport={activeViewport}
                columnCount={columnCount}
              />
            ))
          : <EmptyState message="No rows" />}
      </div>
    </div>
  );
}
```

### Step 4: Run tests

Run: `npm run test -- --run client/src/tests/components/SectionBlock/SectionBlock.test.tsx`
Expected: PASS

### Step 5: Create the SCSS

Create `client/src/components/SectionBlock/SectionBlock.scss`:

```scss
.section-block {
  background: #f0f4f8;
  border: 2px solid #d1d9e0;
  border-radius: 6px;
  padding: 16px;
  border-left: 3px solid transparent;

  &--draft {
    border-left-color: #0071c4;
  }

  &--published {
    border-left-color: #3fa142;
  }

  &--modified {
    border-left-color: #d4a017;
  }

  & + & {
    margin-top: 16px;
  }
}

.section-block__title {
  font-size: 14px;
  font-weight: 700;
  color: #343a40;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 12px;
}

.section-block__body {
  // Rows stack vertically within
}
```

### Step 6: Import SCSS in bundle

In `client/src/styles/bundle.scss`, add:
```scss
@import '../components/SectionBlock/SectionBlock';
```

### Step 7: Commit

```bash
git add client/src/components/SectionBlock/ \
  client/src/tests/components/SectionBlock/ \
  client/src/styles/bundle.scss
git commit -m "feat(grid): add SectionBlock component with double-line border and title"
```

---

## Task 10: Rewrite the GridEditor component to use the new component tree

Replace the proof-of-life `<ul>` tree with the composed component hierarchy: ViewportSwitcher + SectionBlocks.

**Files:**
- Modify: `client/src/components/GridEditor/GridEditor.tsx` (full rewrite)
- Modify: `client/src/tests/components/GridEditor/GridEditor.test.tsx` (rewrite tests)

### Step 1: Rewrite the GridEditor component

Replace the contents of `client/src/components/GridEditor/GridEditor.tsx`:

```tsx
import { useElementTree } from '@/hooks/useElementTree';
import { useViewport } from '@/hooks/useViewport';
import { isSectionNode } from '@/types/elements';
import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import SectionBlock from '@/components/SectionBlock/SectionBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface GridEditorProps {
  readonly areaId: number;
  readonly pageId: number | null;
}

export default function GridEditor({ areaId, pageId }: GridEditorProps) {
  const { data, isLoading, error } = useElementTree(pageId);
  const { viewports, activeViewport, setActiveViewport, columnCount } = useViewport();

  if (isLoading) {
    return (
      <div className="grid-editor" data-area-id={areaId}>
        <p className="grid-editor__loading">Loading elements...</p>
      </div>
    );
  }

  if (error !== null) {
    return (
      <div className="grid-editor" data-area-id={areaId}>
        <p className="grid-editor__error">Failed to load elements: {error.message}</p>
      </div>
    );
  }

  const sections = data !== undefined
    ? Object.values(data).flat().filter(isSectionNode)
    : [];

  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      <ViewportSwitcher
        viewports={viewports}
        activeViewport={activeViewport}
        onViewportChange={setActiveViewport}
      />
      <div className="grid-editor__content">
        {sections.length > 0
          ? sections.map((section) => (
              <SectionBlock
                key={section.id}
                section={section}
                activeViewport={activeViewport}
                columnCount={columnCount}
              />
            ))
          : <EmptyState message="No sections yet" variant="centered" />}
      </div>
    </div>
  );
}
```

### Step 2: Rewrite the GridEditor tests

Update `client/src/tests/components/GridEditor/GridEditor.test.tsx` to test the new visual grid behavior. Key tests:

- Shows loading state
- Shows error state
- Renders viewport switcher with viewport buttons
- Renders section blocks from tree data
- Renders the empty state when no sections exist
- Preserves data-area-id and data-page-id attributes
- Switching viewport updates column fraction badges

The mock data needs `gridSettings` on column nodes and should test that the composed tree renders correctly.

### Step 3: Run all tests

Run: `npm run test -- --run`
Expected: ALL PASS

### Step 4: Run typecheck

Run: `npm run typecheck`
Expected: No errors

### Step 5: Commit

```bash
git add client/src/components/GridEditor/GridEditor.tsx \
  client/src/tests/components/GridEditor/GridEditor.test.tsx
git commit -m "feat(grid): replace proof-of-life tree with visual Layered Blocks grid editor"
```

---

## Task 11: Final QA and build verification

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
Expected: ALL PASS (including the new ElementNode gridSettings tests)

### Step 5: Commit any remaining fixes

If any issues were found and fixed, commit them.

### Step 6: Final commit message

If all clean:
```bash
git status  # verify clean working tree
```
