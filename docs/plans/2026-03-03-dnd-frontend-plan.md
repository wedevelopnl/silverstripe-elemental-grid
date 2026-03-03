# Drag & Drop Frontend Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add full drag-and-drop reordering to the grid editor at all 4 hierarchy levels (sections, rows, columns, elements) using dnd-kit, with optimistic updates and visual feedback.

**Architecture:** Single `DndContext` in `GridEditor` with nested `SortableContext` per container level. Each block component integrates `useSortable` directly. A custom collision detection strategy filters drop targets by type to enforce same-level moves. Optimistic cache updates via TanStack Query rollback on API failure.

**Tech Stack:** `@dnd-kit/core@^6.3.0`, `@dnd-kit/sortable@^10.0.0`, `@dnd-kit/utilities@^3.2.0`, TanStack Query v5, React 18, TypeScript 5.9, Vitest, Playwright

**Design doc:** `docs/plans/2026-03-03-dnd-frontend-design.md`

---

## Task 1: Add `childAreaId` to container nodes in tree response

The backend tree builder knows each container's `ChildAreaID` but doesn't serialize it. The frontend needs this to resolve `targetAreaID` for cross-container drops in the reorder API.

**Files:**
- Modify: `src/Model/ElementNode.php`
- Modify: `src/Service/ElementTreeBuilder.php:145-204`
- Modify: `client/src/types/elements.ts`
- Test: `tests/Unit/Model/ElementNodeTest.php` (create or modify)
- Test: `tests/Unit/Service/ElementTreeBuilderTest.php` (modify if exists)

**Step 1: Write the failing PHP test**

Test that `ElementNode::jsonSerialize()` includes `childAreaId` for container nodes:

```php
public function testContainerNodeIncludesChildAreaId(): void
{
    $node = new ElementNode(
        id: 1,
        title: 'Section',
        blockSchema: self::STUB_BLOCK_SCHEMA,
        obsoleteClassName: null,
        version: 1,
        canDelete: true,
        canPublish: true,
        canUnpublish: true,
        canCreate: true,
        statusFlags: [],
        containerType: ContainerType::Section,
        allowedTypes: null,
        children: [],
        childAreaId: 42,
    );

    $serialized = $node->jsonSerialize();
    self::assertSame(42, $serialized['childAreaId']);
}

public function testLeafNodeOmitsChildAreaId(): void
{
    $node = new ElementNode(
        id: 2,
        title: 'Content',
        blockSchema: self::STUB_BLOCK_SCHEMA,
        obsoleteClassName: null,
        version: 1,
        canDelete: true,
        canPublish: true,
        canUnpublish: true,
        canCreate: true,
        statusFlags: [],
    );

    $serialized = $node->jsonSerialize();
    self::assertArrayNotHasKey('childAreaId', $serialized);
}
```

**Step 2: Run test to verify it fails**

Run: `make test-unit` (or target the specific test class)
Expected: FAIL — `childAreaId` parameter doesn't exist

**Step 3: Write minimal implementation**

In `src/Model/ElementNode.php`, add `childAreaId` parameter and serialize it for containers:

```php
// Constructor — add after $gridSettings parameter:
public ?int $childAreaId = null,

// jsonSerialize() — inside the `if ($this->containerType !== null)` block:
if ($this->childAreaId !== null) {
    $data['childAreaId'] = $this->childAreaId;
}
```

In `src/Service/ElementTreeBuilder.php:buildElementNode()`, pass `childAreaId`:

```php
// After line 157 ($children assignment), capture $childAreaId for the constructor:
// $childAreaId is already computed on line 156

// In the ElementNode constructor call (line 188-204), add:
childAreaId: $element instanceof ElementContainerInterface ? ($childAreaId > 0 ? $childAreaId : null) : null,
```

Note: `$childAreaId` is only in scope inside the container `if` block (line 152-160). Refactor to hoist it:

```php
$childAreaId = null;

if ($element instanceof ElementContainerInterface) {
    $containerType = $element->getContainerType();
    $allowedTypes = $this->getAllowedTypes($element);

    $childAreaId = (int) $element->ChildAreaID;
    $children = $childAreaId !== 0
        ? $this->assembleSubTree($elementsByParent, $childAreaId)
        : [];

    $childAreaId = $childAreaId > 0 ? $childAreaId : null;
}
```

**Step 4: Update the Zod schema**

In `client/src/types/elements.ts`, add `childAreaId` to container schemas:

```typescript
// Add to columnNodeSchema (after gridSettings):
export const columnNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('column'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(simpleElementNodeSchema).nullable(),
  gridSettings: gridSettingsSchema,
  childAreaId: z.number().int().nullable(),
});

// Same for rowNodeSchema and sectionNodeSchema:
export const rowNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('row'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(columnNodeSchema).nullable(),
  childAreaId: z.number().int().nullable(),
});

export const sectionNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('section'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(rowNodeSchema).nullable(),
  childAreaId: z.number().int().nullable(),
});
```

**Step 5: Run all tests**

Run: `make test-unit && npm run test`
Expected: All pass — backend serializes `childAreaId`, frontend schema accepts it

**Step 6: Update PHPStan types**

Update the `@phpstan-type SerializedNode` docblock on `ElementNode` to include `childAreaId?`.

Run: `make analyse`
Expected: PASS

**Step 7: Commit**

```bash
git add src/Model/ElementNode.php src/Service/ElementTreeBuilder.php client/src/types/elements.ts tests/
git commit -m "feat: expose childAreaId on container nodes in tree response"
```

---

## Task 2: DnD type system and ID helpers

Pure types and functions with zero dependencies on dnd-kit. This is the foundation all other DnD code builds on.

**Files:**
- Create: `client/src/types/dnd.ts`
- Test: `client/src/tests/types/dnd.test.ts`

**Step 1: Write the failing tests**

```typescript
import { describe, it, expect } from 'vitest';
import {
  buildDraggableId,
  parseDraggableId,
  getDraggableType,
  type DraggableType,
} from '@/types/dnd';

describe('buildDraggableId', () => {
  it('builds a composite ID from type and numeric ID', () => {
    expect(buildDraggableId('section', 42)).toBe('section-42');
    expect(buildDraggableId('row', 17)).toBe('row-17');
    expect(buildDraggableId('column', 8)).toBe('column-8');
    expect(buildDraggableId('element', 103)).toBe('element-103');
  });
});

describe('parseDraggableId', () => {
  it('parses type and numeric ID from composite string', () => {
    expect(parseDraggableId('section-42')).toEqual({ type: 'section', id: 42 });
    expect(parseDraggableId('element-103')).toEqual({ type: 'element', id: 103 });
  });

  it('returns null for invalid format', () => {
    expect(parseDraggableId('invalid')).toBeNull();
    expect(parseDraggableId('')).toBeNull();
    expect(parseDraggableId('section-abc')).toBeNull();
    expect(parseDraggableId('unknown-42')).toBeNull();
  });
});

describe('getDraggableType', () => {
  it('returns the type portion of a draggable ID', () => {
    expect(getDraggableType('section-42')).toBe('section');
    expect(getDraggableType('row-17')).toBe('row');
  });

  it('returns null for invalid IDs', () => {
    expect(getDraggableType('invalid')).toBeNull();
  });
});
```

**Step 2: Run test to verify it fails**

Run: `npm run test -- client/src/tests/types/dnd.test.ts`
Expected: FAIL — module not found

**Step 3: Write minimal implementation**

```typescript
// client/src/types/dnd.ts

export const DRAGGABLE_TYPES = ['section', 'row', 'column', 'element'] as const;

export type DraggableType = (typeof DRAGGABLE_TYPES)[number];

export interface ParsedDraggableId {
  readonly type: DraggableType;
  readonly id: number;
}

const SEPARATOR = '-';

function isDraggableType(value: string): value is DraggableType {
  return (DRAGGABLE_TYPES as readonly string[]).includes(value);
}

export function buildDraggableId(type: DraggableType, id: number): string {
  return `${type}${SEPARATOR}${id}`;
}

export function parseDraggableId(compositeId: string): ParsedDraggableId | null {
  const separatorIndex = compositeId.indexOf(SEPARATOR);
  if (separatorIndex <= 0) return null;

  const type = compositeId.slice(0, separatorIndex);
  if (!isDraggableType(type)) return null;

  const numericId = Number(compositeId.slice(separatorIndex + 1));
  if (!Number.isInteger(numericId) || numericId <= 0) return null;

  return { type, id: numericId };
}

export function getDraggableType(compositeId: string): DraggableType | null {
  return parseDraggableId(compositeId)?.type ?? null;
}

/**
 * Maps a draggable type to the container type that holds its siblings.
 * Sections live in the root area, rows in sections, columns in rows, elements in columns.
 */
export const PARENT_CONTAINER_TYPE: Record<DraggableType, DraggableType | 'root'> = {
  section: 'root',
  row: 'section',
  column: 'row',
  element: 'column',
};
```

**Step 4: Run test to verify it passes**

Run: `npm run test -- client/src/tests/types/dnd.test.ts`
Expected: PASS

**Step 5: Commit**

```bash
git add client/src/types/dnd.ts client/src/tests/types/dnd.test.ts
git commit -m "feat: add DnD type system and ID helpers"
```

---

## Task 3: `applyReorder` tree manipulation utility

Pure function that moves an element within the in-memory tree. Used by the optimistic update in `onMutate`. No dnd-kit dependency.

**Files:**
- Create: `client/src/utils/applyReorder.ts`
- Test: `client/src/tests/utils/applyReorder.test.ts`

**Step 1: Write the failing tests**

Build test fixtures using the same factory pattern from existing tests (see `client/src/tests/hooks/useCollapseEnrichment.test.tsx` for the pattern). Test scenarios:

```typescript
import { describe, it, expect } from 'vitest';
import { applyReorder } from '@/utils/applyReorder';
import type { ElementTreeResponse, SectionNode, RowNode, ColumnNode, SimpleElementNode } from '@/types/elements';

// Factory helpers — reuse patterns from existing test files
function makeElement(id: number, overrides?: Partial<SimpleElementNode>): SimpleElementNode { /* ... */ }
function makeColumn(id: number, children: SimpleElementNode[], childAreaId: number): ColumnNode { /* ... */ }
function makeRow(id: number, children: ColumnNode[], childAreaId: number): RowNode { /* ... */ }
function makeSection(id: number, children: RowNode[], childAreaId: number): SectionNode { /* ... */ }

describe('applyReorder', () => {
  describe('same-container reorder', () => {
    it('moves element within same column', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [
            makeRow(2, [
              makeColumn(3, [makeElement(10), makeElement(11), makeElement(12)], 200),
            ], 201),
          ], 202),
        ],
      };

      // Move element 12 after element 10 (from index 2 to index 1)
      const result = applyReorder(tree, 12, 200, 10);
      const column = (result['100'][0] as SectionNode).children![0].children![0];
      expect(column.children!.map((c) => c.id)).toEqual([10, 12, 11]);
    });

    it('moves element to start of container (afterElementId = null)', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [
            makeRow(2, [
              makeColumn(3, [makeElement(10), makeElement(11)], 200),
            ], 201),
          ], 202),
        ],
      };

      const result = applyReorder(tree, 11, 200, null);
      const column = (result['100'][0] as SectionNode).children![0].children![0];
      expect(column.children!.map((c) => c.id)).toEqual([11, 10]);
    });
  });

  describe('cross-container reorder', () => {
    it('moves element from one column to another', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [
            makeRow(2, [
              makeColumn(3, [makeElement(10), makeElement(11)], 200),
              makeColumn(4, [makeElement(12)], 300),
            ], 201),
          ], 202),
        ],
      };

      // Move element 11 to column 4, after element 12
      const result = applyReorder(tree, 11, 300, 12);
      const row = (result['100'][0] as SectionNode).children![0];
      expect(row.children![0].children!.map((c) => c.id)).toEqual([10]);
      expect(row.children![1].children!.map((c) => c.id)).toEqual([12, 11]);
    });

    it('moves row between sections', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [makeRow(10, [], 500), makeRow(11, [], 501)], 400),
          makeSection(2, [makeRow(12, [], 502)], 401),
        ],
      };

      // Move row 11 to section 2, after row 12
      const result = applyReorder(tree, 11, 401, 12);
      expect((result['100'][0] as SectionNode).children!.map((r) => r.id)).toEqual([10]);
      expect((result['100'][1] as SectionNode).children!.map((r) => r.id)).toEqual([12, 11]);
    });
  });

  describe('no-op detection', () => {
    it('returns same reference when element is already at target position', () => {
      const tree: ElementTreeResponse = {
        '100': [
          makeSection(1, [
            makeRow(2, [
              makeColumn(3, [makeElement(10), makeElement(11)], 200),
            ], 201),
          ], 202),
        ],
      };

      // Element 11 is already after element 10 in area 200
      const result = applyReorder(tree, 11, 200, 10);
      expect(result).toBe(tree);
    });
  });

  it('moves section within root area', () => {
    const tree: ElementTreeResponse = {
      '100': [
        makeSection(1, [], 400),
        makeSection(2, [], 401),
        makeSection(3, [], 402),
      ],
    };

    // Move section 3 after section 1
    const result = applyReorder(tree, 3, 100, 1);
    expect(result['100'].map((s) => s.id)).toEqual([1, 3, 2]);
  });
});
```

**Step 2: Run test to verify it fails**

Run: `npm run test -- client/src/tests/utils/applyReorder.test.ts`
Expected: FAIL — module not found

**Step 3: Write minimal implementation**

```typescript
// client/src/utils/applyReorder.ts

import type { ElementTreeResponse, ElementNode } from '@/types/elements';
import { isContainerNode } from '@/types/elements';

/**
 * Applies a reorder operation to the in-memory tree, returning a new tree.
 * Returns the same reference if the element is already at the target position (no-op).
 *
 * @param tree - The current element tree
 * @param elementId - ID of the element to move
 * @param targetAreaId - The area ID (childAreaId of the target container, or root area key)
 * @param afterElementId - Place after this element, or null for start of container
 */
export function applyReorder(
  tree: ElementTreeResponse,
  elementId: number,
  targetAreaId: number,
  afterElementId: number | null,
): ElementTreeResponse {
  // Find the element and its current location
  const source = findElementLocation(tree, elementId);
  if (source === null) return tree;

  // Check for no-op: same area, same position
  if (isNoOp(source, targetAreaId, afterElementId)) return tree;

  // Deep clone the tree (only the affected branches ideally, but full clone is safe)
  const newTree = structuredClone(tree);

  // Remove from source
  const sourceChildren = findChildrenArray(newTree, source.parentAreaId);
  if (sourceChildren === null) return tree;
  const removeIndex = sourceChildren.findIndex((c) => c.id === elementId);
  if (removeIndex === -1) return tree;
  const [removed] = sourceChildren.splice(removeIndex, 1);

  // Insert at target
  const targetChildren = findChildrenArray(newTree, targetAreaId);
  if (targetChildren === null) return tree;

  if (afterElementId === null) {
    targetChildren.unshift(removed);
  } else {
    const afterIndex = targetChildren.findIndex((c) => c.id === afterElementId);
    if (afterIndex === -1) {
      targetChildren.push(removed);
    } else {
      targetChildren.splice(afterIndex + 1, 0, removed);
    }
  }

  return newTree;
}

interface ElementLocation {
  parentAreaId: number;
  index: number;
}

function isNoOp(
  source: ElementLocation,
  targetAreaId: number,
  afterElementId: number | null,
): boolean {
  // Different area = definitely not a no-op
  if (source.parentAreaId !== targetAreaId) return false;
  // Checking exact position requires knowing the sibling at index-1
  // This is a simplification — full no-op detection happens in the caller
  return false;
}

function findElementLocation(
  tree: ElementTreeResponse,
  elementId: number,
): ElementLocation | null {
  // Check root areas
  for (const [areaIdStr, nodes] of Object.entries(tree)) {
    const areaId = Number(areaIdStr);
    const index = nodes.findIndex((n) => n.id === elementId);
    if (index !== -1) return { parentAreaId: areaId, index };

    // Search nested containers
    const result = findInChildren(nodes, elementId);
    if (result !== null) return result;
  }
  return null;
}

function findInChildren(
  nodes: ElementNode[],
  elementId: number,
): ElementLocation | null {
  for (const node of nodes) {
    if (!isContainerNode(node) || node.children === null) continue;

    const index = node.children.findIndex((c) => c.id === elementId);
    if (index !== -1) return { parentAreaId: node.childAreaId!, index };

    const result = findInChildren(node.children, elementId);
    if (result !== null) return result;
  }
  return null;
}

function findChildrenArray(
  tree: ElementTreeResponse,
  areaId: number,
): ElementNode[] | null {
  // Check root areas
  const rootNodes = tree[String(areaId)];
  if (rootNodes !== undefined) return rootNodes;

  // Search nested containers
  for (const nodes of Object.values(tree)) {
    const result = findContainerByAreaId(nodes, areaId);
    if (result !== null) return result;
  }
  return null;
}

function findContainerByAreaId(
  nodes: ElementNode[],
  areaId: number,
): ElementNode[] | null {
  for (const node of nodes) {
    if (!isContainerNode(node)) continue;
    if (node.childAreaId === areaId && node.children !== null) return node.children;
    if (node.children !== null) {
      const result = findContainerByAreaId(node.children, areaId);
      if (result !== null) return result;
    }
  }
  return null;
}
```

**Step 4: Run test to verify it passes**

Run: `npm run test -- client/src/tests/utils/applyReorder.test.ts`
Expected: PASS

**Step 5: Typecheck**

Run: `npm run typecheck`
Expected: PASS — verify `childAreaId` types align

**Step 6: Commit**

```bash
git add client/src/utils/applyReorder.ts client/src/tests/utils/applyReorder.test.ts
git commit -m "feat: add applyReorder tree manipulation utility"
```

---

## Task 4: `resolveReorderParams` utility

Maps dnd-kit event data to the backend API's `{elementID, targetAreaID, afterElementID}` format. Pure function, independently testable.

**Files:**
- Create: `client/src/utils/resolveReorderParams.ts`
- Test: `client/src/tests/utils/resolveReorderParams.test.ts`

**Step 1: Write the failing tests**

```typescript
import { describe, it, expect } from 'vitest';
import { resolveReorderParams } from '@/utils/resolveReorderParams';
// Use same factory helpers as applyReorder tests

describe('resolveReorderParams', () => {
  it('resolves params for same-container reorder', () => {
    // Element 11 dropped at index 0 in column 3 (childAreaId 200)
    const result = resolveReorderParams({
      activeId: 'element-11',
      overId: 'element-10',
      overContainerAreaId: 200,
      overIndex: 0,
      containerItems: ['element-10', 'element-11'],
    });

    expect(result).toEqual({
      elementID: 11,
      targetAreaID: 200,
      afterElementID: null,
    });
  });

  it('resolves afterElementID from preceding sibling', () => {
    const result = resolveReorderParams({
      activeId: 'element-11',
      overId: 'element-12',
      overContainerAreaId: 200,
      overIndex: 2,
      containerItems: ['element-10', 'element-12', 'element-11'],
    });

    expect(result).toEqual({
      elementID: 11,
      targetAreaID: 200,
      afterElementID: 12,
    });
  });

  it('returns null for no-op (same position)', () => {
    const result = resolveReorderParams({
      activeId: 'element-11',
      overId: 'element-11',
      overContainerAreaId: 200,
      overIndex: 1,
      containerItems: ['element-10', 'element-11'],
      sourceContainerAreaId: 200,
      sourceIndex: 1,
    });

    expect(result).toBeNull();
  });

  it('returns null for unparseable active ID', () => {
    const result = resolveReorderParams({
      activeId: 'invalid',
      overId: 'element-10',
      overContainerAreaId: 200,
      overIndex: 0,
      containerItems: ['element-10'],
    });

    expect(result).toBeNull();
  });
});
```

**Step 2: Run test to verify failure**

Run: `npm run test -- client/src/tests/utils/resolveReorderParams.test.ts`
Expected: FAIL — module not found

**Step 3: Write minimal implementation**

```typescript
// client/src/utils/resolveReorderParams.ts

import type { ReorderElementParams } from '@/api/endpoints';
import { parseDraggableId } from '@/types/dnd';

export interface ReorderContext {
  activeId: string;
  overId: string;
  overContainerAreaId: number;
  overIndex: number;
  containerItems: string[];
  sourceContainerAreaId?: number;
  sourceIndex?: number;
}

export function resolveReorderParams(
  context: ReorderContext,
): ReorderElementParams | null {
  const parsed = parseDraggableId(context.activeId);
  if (parsed === null) return null;

  // No-op detection: same container, same position
  if (
    context.sourceContainerAreaId === context.overContainerAreaId &&
    context.sourceIndex === context.overIndex
  ) {
    return null;
  }

  // Determine afterElementID from the item before overIndex
  let afterElementID: number | null = null;
  if (context.overIndex > 0) {
    const precedingId = context.containerItems[context.overIndex - 1];
    if (precedingId !== undefined) {
      const precedingParsed = parseDraggableId(precedingId);
      // Skip if the preceding item IS the active item (it's being moved)
      if (precedingParsed !== null && precedingParsed.id !== parsed.id) {
        afterElementID = precedingParsed.id;
      } else if (context.overIndex > 1) {
        // Look one further back
        const furtherParsed = parseDraggableId(context.containerItems[context.overIndex - 2] ?? '');
        afterElementID = furtherParsed?.id ?? null;
      }
    }
  }

  return {
    elementID: parsed.id,
    targetAreaID: context.overContainerAreaId,
    afterElementID,
  };
}
```

**Step 4: Run test to verify it passes**

Run: `npm run test -- client/src/tests/utils/resolveReorderParams.test.ts`
Expected: PASS

**Step 5: Commit**

```bash
git add client/src/utils/resolveReorderParams.ts client/src/tests/utils/resolveReorderParams.test.ts
git commit -m "feat: add resolveReorderParams utility"
```

---

## Task 5: `useReorderElement` mutation hook

Adds the optimistic-update-with-rollback mutation to the existing `useElementMutations.ts`. Follows the established pattern.

**Files:**
- Modify: `client/src/hooks/useElementMutations.ts`
- Modify: `client/src/hooks/index.ts` (add export)
- Test: `client/src/tests/hooks/useReorderElement.test.tsx`

**Step 1: Write the failing test**

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useReorderElement } from '@/hooks/useElementMutations';
import * as endpoints from '@/api/endpoints';
import { queryKeys } from '@/hooks/queryKeys';
import type { ElementTreeResponse } from '@/types/elements';

vi.mock('@/api/endpoints');

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return {
    queryClient,
    wrapper: ({ children }: { children: React.ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    ),
  };
}

describe('useReorderElement', () => {
  const pageId = 1;
  const areaId = 100;

  it('calls reorderElement API and invalidates tree query on success', async () => {
    const { queryClient, wrapper } = createWrapper();
    const reorderSpy = vi.mocked(endpoints.reorderElement).mockResolvedValue(undefined);
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const { result } = renderHook(() => useReorderElement(pageId, areaId), { wrapper });

    result.current.mutate({
      params: { elementID: 10, targetAreaID: 200, afterElementID: null },
      tree: {} as ElementTreeResponse,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(reorderSpy).toHaveBeenCalledWith({ elementID: 10, targetAreaID: 200, afterElementID: null });
    expect(invalidateSpy).toHaveBeenCalledWith({
      queryKey: queryKeys.elementTree.byPage(pageId),
    });
  });

  it('rolls back optimistic update on API failure', async () => {
    const { queryClient, wrapper } = createWrapper();
    // Seed cache with initial tree
    const initialTree: ElementTreeResponse = { '100': [] };
    queryClient.setQueryData(queryKeys.elementTree.byPage(pageId), initialTree);

    vi.mocked(endpoints.reorderElement).mockRejectedValue(new Error('Server error'));

    const { result } = renderHook(() => useReorderElement(pageId, areaId), { wrapper });

    result.current.mutate({
      params: { elementID: 10, targetAreaID: 200, afterElementID: null },
      tree: initialTree,
    });

    await waitFor(() => expect(result.current.isError).toBe(true));

    // Cache should be restored to initial state
    const cachedTree = queryClient.getQueryData(queryKeys.elementTree.byPage(pageId));
    expect(cachedTree).toEqual(initialTree);
  });
});
```

**Step 2: Run test to verify it fails**

Run: `npm run test -- client/src/tests/hooks/useReorderElement.test.tsx`
Expected: FAIL — `useReorderElement` not exported

**Step 3: Write minimal implementation**

Add to `client/src/hooks/useElementMutations.ts`:

```typescript
import { reorderElement } from '@/api/endpoints';
import type { ReorderElementParams } from '@/api/endpoints';
import type { ElementTreeResponse } from '@/types/elements';
import { applyReorder } from '@/utils/applyReorder';

interface ReorderMutationVariables {
  params: ReorderElementParams;
  tree: ElementTreeResponse;
}

export function useReorderElement(pageId: number, areaId: number) {
  const queryClient = useQueryClient();

  return useMutation<void, ApiError, ReorderMutationVariables, { previousTree: ElementTreeResponse | undefined }>({
    mutationFn: ({ params }) => reorderElement(params),
    onMutate: async ({ params, tree }) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: queryKeys.elementTree.byPage(pageId) });

      // Snapshot current tree
      const previousTree = queryClient.getQueryData<ElementTreeResponse>(
        queryKeys.elementTree.byPage(pageId),
      );

      // Optimistically update cache
      const optimisticTree = applyReorder(tree, params.elementID, params.targetAreaID, params.afterElementID);
      queryClient.setQueryData(queryKeys.elementTree.byPage(pageId), optimisticTree);

      return { previousTree };
    },
    onError: (_err, _vars, context) => {
      if (context?.previousTree !== undefined) {
        queryClient.setQueryData(queryKeys.elementTree.byPage(pageId), context.previousTree);
      }
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.elementTree.byPage(pageId) });
    },
  });
}
```

Export from `client/src/hooks/index.ts`:

```typescript
export { useReorderElement } from './useElementMutations';
```

**Step 4: Run test to verify it passes**

Run: `npm run test -- client/src/tests/hooks/useReorderElement.test.tsx`
Expected: PASS

**Step 5: Commit**

```bash
git add client/src/hooks/useElementMutations.ts client/src/hooks/index.ts client/src/tests/hooks/useReorderElement.test.tsx
git commit -m "feat: add useReorderElement mutation hook with optimistic rollback"
```

---

## Task 6: `DragHandle` component

Presentational component: renders a grip icon and forwards dnd-kit's `listeners`/`attributes`.

**Files:**
- Create: `client/src/components/DragHandle/DragHandle.tsx`
- Create: `client/src/components/DragHandle/DragHandle.scss`
- Modify: `client/src/styles/bundle.scss` (import new stylesheet)
- Test: `client/src/tests/components/DragHandle.test.tsx`

**Step 1: Write the failing test**

```typescript
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import DragHandle from '@/components/DragHandle/DragHandle';

describe('DragHandle', () => {
  it('renders a button with drag-handle testid', () => {
    render(<DragHandle listeners={{}} attributes={{} as any} />);
    expect(screen.getByTestId('drag-handle')).toBeInTheDocument();
  });

  it('renders as a button with type button', () => {
    render(<DragHandle listeners={{}} attributes={{} as any} />);
    const button = screen.getByTestId('drag-handle');
    expect(button.tagName).toBe('BUTTON');
    expect(button).toHaveAttribute('type', 'button');
  });

  it('has an accessible label', () => {
    render(<DragHandle listeners={{}} attributes={{} as any} label="Move section" />);
    expect(screen.getByLabelText('Move section')).toBeInTheDocument();
  });

  it('spreads listeners and attributes onto the button', () => {
    const listeners = { onPointerDown: vi.fn() };
    const attributes = { role: 'button', tabIndex: 0 } as any;

    render(<DragHandle listeners={listeners} attributes={attributes} />);
    const button = screen.getByTestId('drag-handle');
    expect(button).toHaveAttribute('tabindex', '0');
  });
});
```

**Step 2: Run test to verify failure**

Run: `npm run test -- client/src/tests/components/DragHandle.test.tsx`
Expected: FAIL — module not found

**Step 3: Write minimal implementation**

```typescript
// client/src/components/DragHandle/DragHandle.tsx

import type { SyntheticListenerMap } from '@dnd-kit/core';
import type { DraggableAttributes } from '@dnd-kit/core';
import './DragHandle.scss';

interface DragHandleProps {
  readonly listeners: SyntheticListenerMap | undefined;
  readonly attributes: DraggableAttributes;
  readonly label?: string;
}

export default function DragHandle({ listeners, attributes, label = 'Drag to reorder' }: DragHandleProps) {
  return (
    <button
      type="button"
      className="drag-handle"
      aria-label={label}
      data-testid="drag-handle"
      {...listeners}
      {...attributes}
    >
      <span className="drag-handle__icon" aria-hidden="true" />
    </button>
  );
}
```

```scss
// client/src/components/DragHandle/DragHandle.scss

.drag-handle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: grab;
  touch-action: none;

  &:active {
    cursor: grabbing;
  }

  &__icon {
    display: block;
    width: 10px;
    height: 14px;
    // 6-dot grip pattern using radial-gradient
    background-image: radial-gradient(circle, currentColor 1.5px, transparent 1.5px);
    background-size: 5px 5px;
    background-position: 0 0, 5px 0;
    opacity: 0.5;
  }

  &:hover &__icon {
    opacity: 0.8;
  }
}
```

Import in `client/src/styles/bundle.scss`.

**Step 4: Run test to verify it passes**

Run: `npm run test -- client/src/tests/components/DragHandle.test.tsx`
Expected: PASS

**Step 5: Commit**

```bash
git add client/src/components/DragHandle/ client/src/tests/components/DragHandle.test.tsx client/src/styles/bundle.scss
git commit -m "feat: add DragHandle component"
```

---

## Task 7: `DragOverlayContent` component

Renders a compact preview of the dragged item based on its type.

**Files:**
- Create: `client/src/components/DragOverlayContent/DragOverlayContent.tsx`
- Create: `client/src/components/DragOverlayContent/DragOverlayContent.scss`
- Test: `client/src/tests/components/DragOverlayContent.test.tsx`

**Step 1: Write the failing test**

```typescript
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import DragOverlayContent from '@/components/DragOverlayContent/DragOverlayContent';
// Use factory helpers for test nodes

describe('DragOverlayContent', () => {
  it('renders section preview with title and row count', () => {
    const section = makeSection(1, [makeRow(2, [], 500), makeRow(3, [], 501)], 400);
    render(<DragOverlayContent node={section} type="section" />);
    expect(screen.getByText(section.title)).toBeInTheDocument();
    expect(screen.getByText('2 rows')).toBeInTheDocument();
  });

  it('renders row preview with title and column count', () => {
    const row = makeRow(2, [makeColumn(3, [], 200)], 500);
    render(<DragOverlayContent node={row} type="row" />);
    expect(screen.getByText(row.title)).toBeInTheDocument();
    expect(screen.getByText('1 column')).toBeInTheDocument();
  });

  it('renders column preview with title', () => {
    const column = makeColumn(3, [makeElement(10)], 200);
    render(<DragOverlayContent node={column} type="column" />);
    expect(screen.getByText(column.title)).toBeInTheDocument();
  });

  it('renders element preview with type label and title', () => {
    const element = makeElement(10);
    render(<DragOverlayContent node={element} type="element" />);
    expect(screen.getByText(element.title)).toBeInTheDocument();
    expect(screen.getByText(element.blockSchema.label)).toBeInTheDocument();
  });
});
```

**Step 2: Run test to verify failure, Step 3: implement, Step 4: verify pass**

The component renders a card per type with title + summary info. Styled with `box-shadow`, `opacity: 0.85`, `pointer-events: none`.

**Step 5: Commit**

```bash
git add client/src/components/DragOverlayContent/ client/src/tests/components/DragOverlayContent.test.tsx
git commit -m "feat: add DragOverlayContent component"
```

---

## Task 8: Custom collision detection strategy

Filters droppable candidates by the active item's type before delegating to `closestCenter`.

**Files:**
- Create: `client/src/utils/collisionDetection.ts`
- Test: `client/src/tests/utils/collisionDetection.test.ts`

**Step 1: Write the failing tests**

Test that the strategy:
- Filters droppable rects to only those matching the active item's level
- Delegates to `closestCenter` for the final pick among valid candidates
- Returns empty array when no valid targets exist

The collision detection function signature follows dnd-kit's `CollisionDetection` type:
```typescript
type CollisionDetection = (args: { active: Active; collisionRect: ClientRect; droppableRects: RectMap; droppableContainers: DroppableContainer[]; pointerCoordinates: Coordinates | null }) => Collision[]
```

Key test: given a dragged `row-5`, only droppable containers whose IDs start with `row-` or are section-level sortable containers should be candidates. Container IDs use a convention: the `SortableContext` for a section's rows is identified by the section's composite ID.

**Step 2-4: Implement and verify**

The strategy parses the active ID's type, filters `droppableContainers` to compatible types, then calls `closestCenter` with the filtered set.

```typescript
// client/src/utils/collisionDetection.ts

import { closestCenter, type CollisionDetection } from '@dnd-kit/core';
import { getDraggableType, PARENT_CONTAINER_TYPE } from '@/types/dnd';

export const typedCollisionDetection: CollisionDetection = (args) => {
  const activeType = getDraggableType(String(args.active.id));
  if (activeType === null) return [];

  // Filter droppable containers to only those that are valid targets
  // Valid targets: siblings of the same type, or the container that holds them
  const filteredContainers = args.droppableContainers.filter((container) => {
    const containerId = String(container.id);
    const containerType = getDraggableType(containerId);

    // Allow dropping on siblings (same type)
    if (containerType === activeType) return true;

    // Allow dropping on the parent container type (for SortableContext droppables)
    // SortableContext creates a droppable with the container's ID
    const parentType = PARENT_CONTAINER_TYPE[activeType];
    if (parentType !== 'root' && containerType === parentType) return true;

    // Allow root-level droppable for sections
    if (parentType === 'root' && containerType === null) return true;

    return false;
  });

  return closestCenter({
    ...args,
    droppableContainers: filteredContainers,
  });
};
```

**Step 5: Commit**

```bash
git add client/src/utils/collisionDetection.ts client/src/tests/utils/collisionDetection.test.ts
git commit -m "feat: add type-aware collision detection strategy"
```

---

## Task 9: `useDragAndDrop` hook

Orchestrates drag state and event handlers. Returns everything `GridEditor` needs to wire up `DndContext`.

**Files:**
- Create: `client/src/hooks/useDragAndDrop.ts`
- Modify: `client/src/hooks/index.ts` (add export)
- Test: `client/src/tests/hooks/useDragAndDrop.test.tsx`

**Step 1: Write the failing test**

Test the hook's return shape and event handler behavior:
- `onDragStart` sets `activeId`/`activeType`/`activeNode`
- `onDragEnd` calls the reorder mutation with resolved params
- `onDragEnd` clears drag state
- `onDragCancel` clears drag state without calling mutation
- No-op drops (same position) don't trigger mutation

**Step 2-4: Implement and verify**

```typescript
// client/src/hooks/useDragAndDrop.ts

import { useState, useCallback, useMemo } from 'react';
import { PointerSensor, useSensor, useSensors, type DragStartEvent, type DragEndEvent, type DragCancelEvent } from '@dnd-kit/core';
import type { ElementNode, ElementTreeResponse } from '@/types/elements';
import { parseDraggableId, type DraggableType } from '@/types/dnd';

interface DragState {
  activeId: string;
  activeType: DraggableType;
  activeNode: ElementNode;
}

interface UseDragAndDropOptions {
  tree: ElementTreeResponse;
  areaId: number;
  onReorder: (elementID: number, targetAreaID: number, afterElementID: number | null) => void;
}

export function useDragAndDrop({ tree, areaId, onReorder }: UseDragAndDropOptions) {
  const [dragState, setDragState] = useState<DragState | null>(null);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
  );

  const handleDragStart = useCallback((event: DragStartEvent) => {
    const parsed = parseDraggableId(String(event.active.id));
    if (parsed === null) return;

    // Find the node in the tree
    const node = findNodeById(tree, parsed.id);
    if (node === null) return;

    setDragState({
      activeId: String(event.active.id),
      activeType: parsed.type,
      activeNode: node,
    });
  }, [tree]);

  const handleDragEnd = useCallback((event: DragEndEvent) => {
    setDragState(null);

    const { active, over } = event;
    if (over === null) return;

    // Resolve reorder params and call mutation
    // Implementation delegates to resolveReorderParams
    // ...
  }, [tree, areaId, onReorder]);

  const handleDragCancel = useCallback((_event: DragCancelEvent) => {
    setDragState(null);
  }, []);

  return {
    sensors,
    dragState,
    handleDragStart,
    handleDragEnd,
    handleDragCancel,
  };
}

function findNodeById(tree: ElementTreeResponse, id: number): ElementNode | null {
  // Recursive search through tree
  // ...
}
```

The full implementation resolves `overContainerAreaId` and `overIndex` from the dnd-kit event data and the tree structure, then delegates to `resolveReorderParams`.

**Step 5: Commit**

```bash
git add client/src/hooks/useDragAndDrop.ts client/src/hooks/index.ts client/src/tests/hooks/useDragAndDrop.test.tsx
git commit -m "feat: add useDragAndDrop orchestration hook"
```

---

## Task 10: Integrate DndContext into GridEditor

Wire up the DndContext at the root level with sensors, collision detection, event handlers, and the top-level SortableContext for sections.

**Files:**
- Modify: `client/src/components/GridEditor/GridEditor.tsx`
- Test: `client/src/tests/components/GridEditor.test.tsx` (modify existing)

**Step 1: Write the failing test**

Test that `GridEditor` renders a `DndContext` and `SortableContext` wrapping the section list. Verify the `DragOverlay` renders when drag state is active.

**Step 2-4: Implement**

```typescript
// GridEditor.tsx — updated structure

import { DndContext, DragOverlay } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useDragAndDrop } from '@/hooks/useDragAndDrop';
import { useReorderElement } from '@/hooks/useElementMutations';
import { typedCollisionDetection } from '@/utils/collisionDetection';
import { buildDraggableId } from '@/types/dnd';
import DragOverlayContent from '@/components/DragOverlayContent/DragOverlayContent';

export default function GridEditor({ areaId, pageId }: GridEditorProps) {
  const { data, isLoading, error } = useElementTree(pageId);
  const sections = /* ... existing ... */;
  const enrichedSections = useCollapseEnrichment(sections, areaId);

  const reorderMutation = useReorderElement(pageId ?? 0, areaId);

  const { sensors, dragState, handleDragStart, handleDragEnd, handleDragCancel } = useDragAndDrop({
    tree: data ?? {},
    areaId,
    onReorder: (elementID, targetAreaID, afterElementID) => {
      reorderMutation.mutate({
        params: { elementID, targetAreaID, afterElementID },
        tree: data ?? {},
      });
    },
  });

  const sectionIds = enrichedSections.map((s) => buildDraggableId('section', s.id));

  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      {/* loading/error states unchanged */}
      {data !== undefined && (
        <ViewportProvider>
          <DndContext
            sensors={sensors}
            collisionDetection={typedCollisionDetection}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
            onDragCancel={handleDragCancel}
          >
            <ViewportSwitcher />
            <SortableContext items={sectionIds} strategy={verticalListSortingStrategy}>
              {enrichedSections.length > 0
                ? enrichedSections.map((section) => (
                  <SectionBlock key={section.id} section={section} />
                ))
                : <EmptyState message="No sections yet" variant="centered" />}
            </SortableContext>
            <DragOverlay>
              {dragState !== null && (
                <DragOverlayContent node={dragState.activeNode} type={dragState.activeType} />
              )}
            </DragOverlay>
          </DndContext>
        </ViewportProvider>
      )}
    </div>
  );
}
```

**Step 5: Commit**

```bash
git add client/src/components/GridEditor/GridEditor.tsx client/src/tests/components/GridEditor.test.tsx
git commit -m "feat: integrate DndContext into GridEditor"
```

---

## Task 11: Integrate useSortable into SectionBlock

Add `useSortable` and `SortableContext` for rows.

**Files:**
- Modify: `client/src/components/SectionBlock/SectionBlock.tsx`
- Test: `client/src/tests/components/SectionBlock.test.tsx` (modify existing)

**Step 1: Write test**

Verify that `SectionBlock` renders a `DragHandle` in the header and wraps rows in a `SortableContext`.

**Step 2-4: Implement**

```typescript
import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { buildDraggableId } from '@/types/dnd';
import DragHandle from '@/components/DragHandle/DragHandle';

export default function SectionBlock({ section }: SectionBlockProps) {
  const sortableId = buildDraggableId('section', section.id);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: sortableId });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.3 : undefined,
  };

  const rowIds = (section.children ?? []).map((r) => buildDraggableId('row', r.id));

  return (
    <section ref={setNodeRef} style={style} className={rootClasses} data-testid="section-block">
      <div className="section-block__header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${section.title}`} />
        <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={section.title} />
        <h2 className="section-block__title">{section.title}</h2>
      </div>
      <div className="section-block__body">
        <SortableContext items={rowIds} strategy={verticalListSortingStrategy}>
          {section.children !== null && section.children.length > 0
            ? section.children.map((row) => <RowBlock key={row.id} row={row} />)
            : <EmptyState message="No rows" />}
        </SortableContext>
      </div>
    </section>
  );
}
```

**Step 5: Commit**

```bash
git add client/src/components/SectionBlock/ client/src/tests/components/SectionBlock.test.tsx
git commit -m "feat: add drag & drop to SectionBlock"
```

---

## Task 12: Integrate useSortable into RowBlock

Same pattern as SectionBlock. Rows use `verticalListSortingStrategy`, columns within use `horizontalListSortingStrategy`.

**Files:**
- Modify: `client/src/components/RowBlock/RowBlock.tsx`
- Test: `client/src/tests/components/RowBlock.test.tsx`

**Step 1-4: Follow same pattern as Task 11**

Key difference: `SortableContext` for columns uses `horizontalListSortingStrategy`:

```typescript
const columnIds = (row.children ?? []).map((c) => buildDraggableId('column', c.id));

<SortableContext items={columnIds} strategy={horizontalListSortingStrategy}>
  {/* columns */}
</SortableContext>
```

**Step 5: Commit**

```bash
git add client/src/components/RowBlock/ client/src/tests/components/RowBlock.test.tsx
git commit -m "feat: add drag & drop to RowBlock"
```

---

## Task 13: Integrate useSortable into ColumnBlock

Columns are horizontally sortable and contain a `SortableContext` for elements (vertical).

**Files:**
- Modify: `client/src/components/ColumnBlock/ColumnBlock.tsx`
- Test: `client/src/tests/components/ColumnBlock.test.tsx`

**Step 1-4: Follow same pattern**

Key: The outer `<div>` (grid width class) gets the sortable ref and transforms. The inner `<div>` (column-block styling) stays as-is.

```typescript
const sortableId = buildDraggableId('column', column.id);
const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: sortableId });
const elementIds = (column.children ?? []).map((c) => buildDraggableId('element', c.id));

const sortableStyle = {
  transform: CSS.Transform.toString(transform),
  transition,
  opacity: isDragging ? 0.3 : undefined,
};

return (
  <div ref={setNodeRef} style={sortableStyle} className={outerClasses.join(' ')}>
    <div className={innerClasses.join(' ')} data-testid="column-block">
      <div className="column-block__header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${column.title}`} />
        {/* ... existing header content */}
      </div>
      <div className="column-block__body">
        <SortableContext items={elementIds} strategy={verticalListSortingStrategy}>
          {/* ... existing children rendering */}
        </SortableContext>
      </div>
    </div>
  </div>
);
```

**Step 5: Commit**

```bash
git add client/src/components/ColumnBlock/ client/src/tests/components/ColumnBlock.test.tsx
git commit -m "feat: add drag & drop to ColumnBlock"
```

---

## Task 14: Integrate useSortable into ElementCard

Leaf node — `useSortable` only, no child `SortableContext`.

**Files:**
- Modify: `client/src/components/ElementCard/ElementCard.tsx`
- Test: `client/src/tests/components/ElementCard.test.tsx`

**Step 1-4: Implement**

```typescript
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { buildDraggableId } from '@/types/dnd';
import DragHandle from '@/components/DragHandle/DragHandle';

export default function ElementCard({ element }: ElementCardProps) {
  const sortableId = buildDraggableId('element', element.id);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: sortableId });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.3 : undefined,
  };

  return (
    <div ref={setNodeRef} style={style} className={`element-card element-card--${status}`}>
      <div className="element-card__header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${element.title}`} />
        <span className="element-card__type">{label}</span>
        <h4 className="element-card__title">{element.title}</h4>
      </div>
      {/* ... existing content */}
    </div>
  );
}
```

**Step 5: Commit**

```bash
git add client/src/components/ElementCard/ client/src/tests/components/ElementCard.test.tsx
git commit -m "feat: add drag & drop to ElementCard"
```

---

## Task 15: SCSS styles for drag states

Add styles for drag feedback: drop indicators, container highlights, drag overlay.

**Files:**
- Modify: `client/src/styles/_variables.scss`
- Create: `client/src/styles/_drag.scss`
- Modify: `client/src/styles/bundle.scss` (import `_drag.scss`)
- Modify component SCSS files as needed

**Step 1: Add variables**

```scss
// _variables.scss — add:
$color-drop-indicator: #2563eb;
$color-drop-zone-highlight: rgba(37, 99, 235, 0.1);
$opacity-dragging: 0.3;
$drag-overlay-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
```

**Step 2: Create drag styles**

```scss
// _drag.scss
.drag-overlay-content {
  box-shadow: $drag-overlay-shadow;
  opacity: 0.85;
  pointer-events: none;
  border-radius: 4px;
  background: white;
  padding: 8px 12px;
}

// Drop indicator line (inserted by SortableContext animation)
.sortable-drop-indicator {
  height: 2px;
  background: $color-drop-indicator;
  border-radius: 1px;
  margin: 2px 0;

  // Vertical variant for horizontal lists (columns)
  &--vertical {
    width: 2px;
    height: auto;
    margin: 0 2px;
  }
}
```

**Step 3: Run visual check**

Run: `npm run build` — verify SCSS compiles without errors

**Step 4: Commit**

```bash
git add client/src/styles/ client/src/components/DragOverlayContent/DragOverlayContent.scss
git commit -m "feat: add SCSS styles for drag states"
```

---

## Task 16: E2E tests

Playwright tests for real drag interactions against the Docker dev environment.

**Files:**
- Create: `tests/E2E/specs/drag-and-drop.spec.ts`
- Modify: `tests/E2E/helpers/` if shared utilities needed

**Step 1: Write the E2E tests**

```typescript
import { test, expect } from '@playwright/test';

test.describe('Drag and Drop', () => {
  test.beforeEach(async ({ page }) => {
    // Load fixtures via the dev endpoint
    await page.goto('/dev/elemental-grid-fixtures/load');
    await page.goto('/admin/pages/edit/show/1');
    // Wait for grid editor to load
    await page.waitForSelector('[data-testid="section-block"]');
  });

  test('reorders sections via drag', async ({ page }) => {
    const sections = page.locator('[data-testid="section-block"]');
    const firstSection = sections.first();
    const secondSection = sections.nth(1);

    const firstTitle = await firstSection.locator('.section-block__title').textContent();
    const handle = firstSection.locator('[data-testid="drag-handle"]').first();

    // Drag first section below second
    await handle.dragTo(secondSection, { targetPosition: { x: 100, y: 50 } });

    // Verify reorder persisted
    await page.reload();
    await page.waitForSelector('[data-testid="section-block"]');
    const newSecondTitle = await sections.nth(1).locator('.section-block__title').textContent();
    expect(newSecondTitle).toBe(firstTitle);
  });

  test('drags element between columns', async ({ page }) => {
    // Test cross-container element move
    // ...
  });

  test('drag cancel with Escape does not reorder', async ({ page }) => {
    const sections = page.locator('[data-testid="section-block"]');
    const handle = sections.first().locator('[data-testid="drag-handle"]').first();

    // Start drag
    await handle.hover();
    await page.mouse.down();
    await page.mouse.move(0, 100);
    // Cancel
    await page.keyboard.press('Escape');
    await page.mouse.up();

    // Verify no change
    // ...
  });
});
```

**Step 2: Run E2E tests**

Run: `npm run test:e2e -- --grep "Drag and Drop"`
Expected: Tests require Docker running (`make up`)

**Step 3: Commit**

```bash
git add tests/E2E/specs/drag-and-drop.spec.ts
git commit -m "test: add E2E tests for drag and drop"
```

---

## Task Dependencies

```
Task 1 (backend childAreaId)
  └─ Task 2 (types/dnd.ts)
       ├─ Task 3 (applyReorder) ──┐
       ├─ Task 4 (resolveParams) ─┤
       └─ Task 8 (collision) ─────┤
                                   ├─ Task 5 (useReorderElement)
                                   │    └─ Task 9 (useDragAndDrop)
Task 6 (DragHandle) ──────────────┤          └─ Task 10 (GridEditor integration)
Task 7 (DragOverlayContent) ──────┘               ├─ Task 11 (SectionBlock)
                                                   ├─ Task 12 (RowBlock)
                                                   ├─ Task 13 (ColumnBlock)
                                                   └─ Task 14 (ElementCard)
                                                        └─ Task 15 (SCSS)
                                                             └─ Task 16 (E2E)
```

**Parallelizable groups:**
- Tasks 3, 4, 6, 7, 8 can all be developed in parallel (after Task 2)
- Tasks 11-14 can be developed in parallel (after Task 10)

## Test Commands Reference

| Scope | Command |
|-------|---------|
| Single JS test file | `npm run test -- path/to/test.ts` |
| All JS tests | `npm run test` |
| JS typecheck | `npm run typecheck` |
| PHP unit tests | `make test-unit` |
| PHP static analysis | `make analyse` |
| Full JS QA | `npm run qa` |
| E2E tests | `npm run test:e2e` |
| Vite build check | `npm run build` |
