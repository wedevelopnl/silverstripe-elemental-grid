# Drag & Drop Frontend Design

## Context

The backend reorder API (`PATCH /api/reorder`) is production-ready, accepting `{elementID, targetAreaID, afterElementID}` and returning 204 on success or 422 with validation errors. The frontend has dnd-kit packages installed (`@dnd-kit/core`, `@dnd-kit/sortable`, `@dnd-kit/utilities`) but zero drag interaction code. This design covers the full frontend implementation.

## Requirements

- All 4 hierarchy levels are draggable: sections, rows, columns, elements
- Same-level moves only (no type promotion/demotion)
- Cross-container moves allowed (e.g., row to different section, column to different row)
- Optimistic updates with rollback on API failure
- Drag overlay + drop zone indicators for visual feedback
- Drag handle icon to initiate (prevents accidental drags)
- Invalid drop targets are visually ignored (collision detection filters them out)
- Viewport-independent (sort order is global, not per-viewport)

## Architecture

**Approach: Single DndContext with nested SortableContexts**

One `DndContext` wraps the entire `GridEditor` inside `ViewportProvider`. Each container level gets its own `SortableContext` with its children's IDs. A custom collision detection strategy filters candidates by the dragged item's type.

### ID Scheme

Each sortable item gets a composite string ID encoding its type and database ID:

```
section-42, row-17, column-8, element-103
```

Drag handlers parse the type from the ID to enforce same-level rules.

### SortableContext Nesting

```
DndContext (in GridEditor)
├─ SortableContext items={['section-1','section-2',...]}
│  ├─ SortableSection (section-1)
│  │  └─ SortableContext items={['row-5','row-6',...]}
│  │     ├─ SortableRow (row-5)
│  │     │  └─ SortableContext items={['column-10','column-11',...]}
│  │     │     ├─ SortableColumn (column-10)
│  │     │     │  └─ SortableContext items={['element-50','element-51',...]}
│  │     │     │     ├─ SortableElement (element-50)
│  │     │     │     └─ SortableElement (element-51)
│  │     │     └─ SortableColumn (column-11)
│  │     └─ SortableRow (row-6)
│  └─ SortableSection (section-2)
└─ DragOverlay (portal, renders preview of active item)
```

### Component Changes

Existing block components integrate `useSortable` directly (no new wrapper components):

- `SectionBlock` → adds `useSortable`, wraps children in `SortableContext`
- `RowBlock` → same pattern
- `ColumnBlock` → same pattern
- `ElementCard` → adds `useSortable` (leaf, no child context)

### New Files

| File | Purpose |
|------|---------|
| `client/src/hooks/useDragAndDrop.ts` | `onDragStart`/`onDragOver`/`onDragEnd` handler logic |
| `client/src/components/DragOverlay/` | Floating preview for the active dragged item |
| `client/src/components/DragHandle/` | Grip icon used across all draggable levels |
| `client/src/types/dnd.ts` | DnD types (`DraggableType`, ID parse/build helpers) |

## Drag Interaction

### Drag Handle

A `DragHandle` component rendered in each block's header, next to `CollapseToggle`. Renders a 6-dot grip pattern via CSS. Receives `listeners` and `attributes` from `useSortable()`.

```
[⠿ grip] [▸ collapse] Section Title
[⠿ grip] [▸ collapse] Row Title
[⠿ grip] [▸ collapse] Column Badge
[⠿ grip] Element Type — Element Title
```

### Sensors

- `PointerSensor` with `activationConstraint: { distance: 8 }` — 8px movement threshold prevents accidental drags
- No keyboard sensor initially

### Drag Overlay

Rendered as the last child inside `DndContext` using a portal. On drag start, the active item's ID, type, and node data are stored in local state. The overlay renders a compact preview:

- **Section**: title + row count
- **Row**: title + column count
- **Column**: title + width badge
- **Element**: full `ElementCard` (already compact)

Styled with elevation shadow and `opacity: 0.85`. The original item in the tree gets `opacity: 0.3` to show its origin.

## Drop Zones & Collision Detection

### Custom Collision Strategy

1. Parse the dragged item's type from its ID
2. Filter collision candidates to containers accepting that type:
   - `section` → top-level section list only
   - `row` → `SortableContext`s inside sections
   - `column` → `SortableContext`s inside rows
   - `element` → `SortableContext`s inside columns
3. Delegate to `closestCenter` among valid candidates

### Per-Level Sorting Strategies

| Level | Layout | Strategy |
|-------|--------|----------|
| Sections in page | vertical | `verticalListSortingStrategy` |
| Rows in section | vertical | `verticalListSortingStrategy` |
| Columns in row | horizontal | `horizontalListSortingStrategy` |
| Elements in column | vertical | `verticalListSortingStrategy` |

The sorting strategy determines how siblings animate to make space and how before/after is resolved (above/below center for vertical, left/right for horizontal).

### Drop Indicators

- Horizontal insertion line (2px, accent color) between vertical siblings
- Vertical insertion line between columns (horizontal layout)
- Target container gets highlighted border for cross-container drops
- Invalid containers show no indicator (filtered by collision detection)

### Mapping to API

`resolveReorderParams` derives API params from dnd-kit's `onDragEnd` event:

- `elementID`: parsed from `active.id`
- `targetAreaID`: looked up from the tree (the elemental area ID of the drop container)
- `afterElementID`: the sibling at `index - 1` in the target container, or `null` if index 0

No-op detection: if dropped in original position (same parent, same index), skip the mutation.

## Optimistic Updates & State Management

### Drag State

Local state in `GridEditor` via the `useDragAndDrop` hook:

```ts
interface DragState {
  activeId: string | null;
  activeType: DraggableType;
  activeNode: ElementNode;
}
```

Set on `onDragStart`, cleared on `onDragEnd`/`onDragCancel`. Ephemeral UI state only.

### useReorderElement Mutation

Follows the existing mutation pattern with optimistic cache updates:

1. **`onMutate`**: snapshot cache → apply move to cached tree → write back → return snapshot
2. **`onError`**: restore snapshot from context
3. **`onSettled`**: invalidate query to refetch authoritative tree

### Tree Manipulation Utility

Pure function `applyReorder(tree, elementId, targetParentId, afterElementId)`:

- Deep-clones source and target parent branches
- Removes element from current parent's `children`
- Inserts at correct position in target parent's `children`
- Returns updated tree

Independently testable, used by `onMutate`.

## Error Handling

- **API failure (network/5xx)**: rollback to snapshot, show inline error banner (auto-dismiss 5s), `onSettled` refetch heals state
- **Validation rejection (422)**: same rollback, display API error message in banner
- **Drag cancellation (Escape / outside drop)**: `onDragCancel` fires, no mutation, drag state resets
- **Concurrent drags**: impossible (single `DndContext`). If a mutation is in-flight from a previous drag, `onSettled` invalidation self-heals any drift
- **Stale tree**: drop may fail with 422, rollback restores consistent client state, refetch brings current server state

## Testing Strategy

### Unit Tests (Vitest)

- `applyReorder` — same-parent reorder, cross-parent move, move to start/end, no-op detection
- `resolveReorderParams` — mock event data for each level
- `parseDraggableId` / `buildDraggableId` — ID helpers
- Collision detection filter — verify type-based candidate filtering

### Component Tests (Vitest + RTL)

- `DragHandle` — renders grip, forwards listeners/attributes
- `DragOverlay` previews — correct compact preview per node type
- `useReorderElement` — optimistic cache update, rollback on error, invalidation

### Integration Tests (Vitest + RTL)

- `GridEditor` with `DndContext` — `SortableContext` items derived correctly from tree
- Drop indicator visibility based on drag state

### E2E Tests (Playwright)

- Drag section to new position, verify persistence after reload
- Drag element cross-column, verify move
- Drag column within row (horizontal), verify reorder
- Drag and cancel (Escape), verify no change

E2E tests provide the highest confidence since dnd-kit interactions are hard to fully simulate in jsdom.
