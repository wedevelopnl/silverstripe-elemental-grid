# Drag and Drop Architecture

## Overview

The drag-and-drop system enables visual reordering of elements within the grid editor. It supports both same-container reordering (moving a row within a section) and cross-container moves (moving a row to a different section). The implementation spans a React frontend using dnd-kit and a PHP backend using a layered service architecture.

## System Boundary

```
┌─────────────────────────────────────────────────────────────┐
│ Frontend (React)                                            │
│                                                             │
│  GridEditor                                                 │
│    ├── DnDContext (dnd-kit)                                 │
│    │     ├── Sensors (pointer, 8px activation threshold)    │
│    │     ├── Collision detection (type-aware filtering)      │
│    │     └── Event handlers (start / end / cancel)          │
│    │                                                        │
│    ├── SortableContexts (nested, one per container area)    │
│    │     ├── Section level (root area)                      │
│    │     ├── Row level (section's child area)               │
│    │     ├── Column level (row's child area)                │
│    │     └── Element level (column's child area)            │
│    │                                                        │
│    └── Optimistic update pipeline                           │
│          ├── Snapshot current tree                           │
│          ├── Apply reorder locally (applyReorder)           │
│          ├── Update query cache                             │
│          └── Rollback on server error                       │
│                                                             │
├─────────────────────── PATCH /api/reorder ──────────────────┤
│                                                             │
│ Backend (PHP)                                               │
│                                                             │
│  ElementalGridController                                    │
│    ├── Request validation (CSRF, payload shape)             │
│    ├── Permission checks (canEdit on element + areas)       │
│    └── Delegates to ReorderService                          │
│                                                             │
│  ReorderService (orchestrator)                              │
│    ├── Phase 1: ReorderValidator (hierarchy rules)          │
│    ├── Phase 2: ReorderExecutor (sort calculation)          │
│    └── Phase 3: ElementPersistenceService (write to DB)     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Data Model

### Element Tree

The API serves a nested tree keyed by area ID:

```
{ [areaId: string]: ElementNode[] }
```

Each `ElementNode` is a discriminated union on `containerType`:

| Type      | containerType | Has children | Has gridSettings |
|-----------|--------------|--------------|------------------|
| Section   | `'section'`  | RowNode[]    | No               |
| Row       | `'row'`      | ColumnNode[] | No               |
| Column    | `'column'`   | SimpleNode[] | Yes              |
| Element   | _(absent)_   | No           | No               |

Every node carries `id` (database primary key) and `parentAreaId` (the area it belongs to). Container nodes additionally carry `childAreaId` (the area that holds their children).

### Reorder Contract

The reorder operation is expressed as three values:

| Field           | Type           | Meaning                                    |
|-----------------|----------------|--------------------------------------------|
| `elementID`     | positive int   | The element being moved                    |
| `targetAreaID`  | positive int   | The area to place it in                    |
| `afterElementID`| positive int / null | Insert after this sibling, or `null` for first position |

This contract is shared between the frontend optimistic update and the backend API endpoint.

## Frontend Architecture

### Lookup Maps

The tree is a nested structure optimized for rendering, not for lookups. Two maps provide O(1) access during drag operations:

- **nodeMap** (`Map<id, ElementNode>`) — find any node by database ID
- **childrenByAreaId** (`Map<areaId, ElementNode[]>`) — find siblings of any node

Maps are built once per tree change via `useMemo`. Every drag hover and drop resolution reads from these maps rather than walking the tree.

### Composite Draggable IDs

dnd-kit identifies draggables and droppables by string ID. The system uses composite IDs in the format `type-numericId` (e.g., `row-42`, `column-17`). This encodes the hierarchy level into the ID, which the collision detection system uses to filter valid drop targets.

### Type-Aware Collision Detection

Before dnd-kit's default `closestCenter` algorithm runs, a filter removes invalid drop targets based on hierarchy rules:

- A **section** can only drop on other sections or the root area
- A **row** can only drop on other rows or into a section
- A **column** can only drop on other columns or into a row
- An **element** can only drop on other elements or into a column

This prevents the user from seeing invalid drop indicators. The filtering happens entirely on the client; the backend validates independently.

### Nested SortableContexts

Each container's children live in their own `SortableContext` with that container's composite child IDs. This creates a hierarchy of sortable regions:

```
SortableContext (root area → section IDs)
  └── SortableContext (section's child area → row IDs)
        └── SortableContext (row's child area → column IDs)
              └── SortableContext (column's child area → element IDs)
```

On drop, the system determines the target container by comparing the active item's type against the over item's type. Same type means sibling reorder (use parent area). Different type means cross-container move (use container's child area).

### Optimistic Update Pipeline

```
User drops element
  │
  ├─ resolveReorderParams()
  │    ├─ No-op? (same area + same index) → abort, no mutation
  │    └─ Compute { elementID, targetAreaID, afterElementID }
  │
  ├─ Mutation fires (TanStack Query)
  │    ├─ onMutate: snapshot cache, apply applyReorder(), update cache
  │    ├─ onError: restore snapshot (rollback)
  │    └─ onSettled: invalidate query (server reconciliation)
  │
  └─ applyReorder() (pure function)
       ├─ No-op detection → return same tree reference
       ├─ structuredClone() the tree
       ├─ Splice element from source children array
       ├─ Update parentAreaId if cross-area
       ├─ Insert into target children array
       └─ Preserve references for unaffected root areas
```

The no-op detection and reference preservation are deliberate: React skips re-rendering subtrees whose root reference hasn't changed.

### Enrichment Layer

Raw tree nodes lack UI state. An enrichment pass adds:

- **sortableId** — the composite ID for dnd-kit registration
- **childSortableIds** — ordered child IDs for the nested `SortableContext`
- **isCollapsed / toggle** — per-node collapse state persisted to localStorage

Enrichment runs once per tree change or collapse state change. It does not run during drag (only on drop, when the tree updates).

### Re-render Characteristics

A drop replaces the cached tree, which causes the full component tree to re-render. This is a conscious tradeoff:

- The tree is immutable — `structuredClone` produces new references for affected subtrees
- All block components receive new enriched props
- No `React.memo` boundaries exist between GridEditor and leaf components

For the expected scale (dozens of sections, not hundreds), this produces no measurable latency. The architecture supports adding `React.memo` boundaries later if scale demands it, without structural changes.

## Backend Architecture

### Layered Service Design

```
Controller (HTTP concerns)
  └── ReorderService (orchestration)
        ├── ReorderValidator (hierarchy rules)
        ├── ReorderExecutor (sort calculation)
        └── ElementPersistenceService (database writes)
```

Each layer has a single responsibility and communicates via the `Result` pattern.

### Phase 1: Validation (ReorderValidator)

**Same-area moves** skip validation entirely — reordering within a container cannot violate hierarchy rules.

**Cross-area moves** check two things:
1. **can_be_root** — if the target area belongs to a page, the element must be allowed at root level
2. **allowed_elements / disallowed_elements** — the target container must accept this element type

Validation returns `Result::fail()` with structured errors on violation. No database writes or in-memory mutations occur.

### Phase 2: Sort Calculation (ReorderExecutor)

The executor works entirely in memory:

1. Load siblings of the target area (sorted by `Sort ASC, ID ASC`)
2. Exclude the moved element from the sibling list
3. Resolve the insertion index from `afterElementID`
4. `array_splice()` the element into position
5. Reindex sort values (1-based: 1, 2, 3, ...)
6. Track dirty elements (only those whose `Sort` or `ParentID` actually changed)

For cross-area moves, the source area's siblings are also reindexed to close the gap.

### Phase 3: Persistence (ElementPersistenceService)

Only dirty elements are written. The persistence service catches SilverStripe's `ValidationException` and translates it to `Result::fail()`, maintaining the Result pattern contract through the entire stack.

### Result Pattern

All service-layer operations return `Result<T>` instead of throwing exceptions for expected failures:

```
Result::ok($value)    — success, carries the value
Result::fail($errors) — failure, carries ValidationError[]
```

The controller maps `Result::ok()` to HTTP 204 and `Result::fail()` to HTTP 422 with structured error JSON.

### Permission Model

The controller checks permissions before delegating to the service layer:

- CSRF token validation (SecurityToken)
- `canEdit()` on the element being moved
- `canEdit()` on the target area
- `canEdit()` on the source area (cross-area moves only)

The service layer assumes permissions have been checked and focuses purely on domain logic.

## Consistency Model

The system uses **optimistic concurrency** without explicit locking:

1. Frontend applies the reorder immediately to the local cache
2. Backend processes the request against current database state
3. Frontend reconciles by invalidating the query after the server responds

If the server state has diverged (another user reordered simultaneously), the invalidation fetches the authoritative tree and overwrites the optimistic state. There is no conflict resolution — last write wins at the database level.

## Error Recovery

| Failure Point              | Recovery                                             |
|---------------------------|------------------------------------------------------|
| Network error             | Rollback to pre-mutation snapshot, query stays stale |
| Server validation failure | Rollback to snapshot, display error                  |
| Server persistence error  | Rollback to snapshot, display error                  |
| Stale optimistic state    | `onSettled` invalidation fetches authoritative tree  |
