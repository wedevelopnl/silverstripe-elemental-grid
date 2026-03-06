# Backend Architecture

## Overview

The backend implements a grid-based content block system as a SilverStripe module. It provides a strict three-level container hierarchy (Section > Row > Column > content), a JSON API for the React frontend, and a pluggable CSS framework adapter system. The architecture uses direct polymorphic parent relationships, a layered service design, and the Result pattern for validation flows.

## Data Model

### Element Hierarchy

All grid elements share a single base table (`GridElement`) using SilverStripe's single-table inheritance. The polymorphic `has_one` to `DataObject` allows any element to live under a page or a container without intermediary join tables.

```
SiteTree (page)
  └── Section  [ContainerType::Section]   Zone-scoped, page-level only
        └── Row  [ContainerType::Row]      Cannot be root
              └── Column  [ContainerType::Column]  Stores GridSettings JSON
                    └── (any non-container GridElement)
```

### Polymorphic Parent

```
GridElement
  ParentID    → int     (FK to any DataObject)
  ParentClass → string  (FQCN of the parent record)
  Sort        → int     (ordering within parent, 1-based)
```

Elements link to their parent via `ParentID + ParentClass`. A Section's parent is a `SiteTree` page; a Row's parent is a `Section`; a Column's parent is a `Row`; a content element's parent is a `Column`. This removes the need for intermediary ownership tables — the parent chain is a direct object graph.

The tradeoff: page IDs and element IDs share no namespace separation, so lookup maps must key by the composite `"ParentClass:ParentID"` string, not by `ParentID` alone.

### Container Interface

All three container types implement `ContainerInterface`:

| Method | Returns | Purpose |
|--------|---------|---------|
| `getChildren()` | `HasManyList<GridElement>` | Children of this container |
| `hasChildren()` | `bool` | Whether children exist |
| `getContainerType()` | `ContainerType` | Discriminator enum |

Container behavior (child count summary, simplified class name) lives in `ContainerElementTrait`, shared across Section, Row, and Column.

### Hierarchy Rules (YAML)

| Container | Constraint | Mechanism |
|-----------|-----------|-----------|
| Section | Only Rows as children | `allowed_elements: [Row]` |
| Row | Only Columns as children, cannot be at page level | `allowed_elements: [Column]`, `can_be_root: false` |
| Column | Any non-container content element | `disallowed_elements: [Section, Row, Column]` |

The allowlist approach on Section and Row is strict: only the listed classes are accepted. The blocklist approach on Column is permissive: any `GridElement` subclass is accepted unless explicitly excluded.

### Zone-Scoped Sections

Sections carry a `Zone` field (e.g., `"main"`, `"sidebar"`) that scopes them within a page. Sort values are independent per zone per parent — the main zone has Sort 1, 2, 3 and the sidebar zone independently has Sort 1, 2, 3. All queries (tree loading, sort assignment, reorder) filter by zone at the root level.

### Grid Settings

Column stores viewport-specific layout as a JSON `Text` field:

```json
{
  "xs": { "width": 12, "offset": 0, "visible": true },
  "md": { "width": 6,  "offset": 0, "visible": true },
  "lg": { "width": 4,  "offset": 2, "visible": false }
}
```

Each viewport entry defines width (column span), offset (column start), and visibility. The adapter translates these into framework-specific CSS classes at render time. Defaults are configurable per project via YAML on `Column.default_grid_settings`.

### Auto-Scaffolding

Writing a container on DRAFT stage automatically creates its required child structure:

```
Section::write()
  └── onAfterWrite() → creates Row (if no children)
        └── Row::write()
              └── onAfterWrite() → creates Column (if no children)
```

A single `Section::create()->write()` produces the full three-level tree. Guards ensure idempotency: scaffolding only runs on DRAFT stage and only when the child collection is empty. Column does not auto-scaffold — it only initializes `GridSettings` on first write.

Auto-scaffolding can be disabled per class via `auto_scaffold: false` in YAML.

## System Boundary

```
┌─────────────────────────────────────────────────────────────┐
│ GridController (AdminController)                            │
│                                                             │
│  HTTP concerns                                              │
│    ├── CSRF validation (SecurityToken)                      │
│    ├── JSON body parsing + type validation                  │
│    ├── Permission checks (canView/canEdit/canDelete/...)    │
│    └── Response mapping (Result → HTTP status + JSON)       │
│                                                             │
├──────────────────── JSON API ───────────────────────────────┤
│                                                             │
│  Service Layer                                              │
│    ├── GridTreeBuilder (read path)                          │
│    │     └── GridElementRepositoryInterface                 │
│    │                                                        │
│    ├── ReorderService (reorder orchestration)               │
│    │     ├── ReorderValidatorInterface                      │
│    │     ├── ReorderExecutorInterface                       │
│    │     └── ElementPersistenceService                      │
│    │                                                        │
│    └── ElementPersistenceService (write path)               │
│          └── ORM write + ValidationException translation    │
│                                                             │
│  Validation Layer                                           │
│    ├── HierarchyValidationExtension (write-time hook)       │
│    │     └── HierarchyValidatorInterface                    │
│    └── ElementAllowanceTrait (shared allowlist/blocklist)   │
│                                                             │
│  Repository Layer                                           │
│    └── OrmGridElementRepository                             │
│          └── Composite key queries (ParentClass:ParentID)   │
│                                                             │
├──────────────────── Rendering ──────────────────────────────┤
│                                                             │
│  Grid Adapter System                                        │
│    ├── GridAdapterInterface (12 methods)                    │
│    ├── GridAdapterConfiguration trait (YAML overrides)      │
│    └── Adapters: Bootstrap, Tailwind, Bulma                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## API Layer

### Endpoints

`GridController` extends `AdminController` and uses property injection (`$dependencies`) for all service dependencies. Every mutating endpoint validates the CSRF token and checks permissions before delegating to the service layer.

| Method | Route | Purpose | Response |
|--------|-------|---------|----------|
| GET | `api/readTree/{PageID}/{Zone}` | Load element tree | 200 + JSON tree |
| POST | `api/create` | Create element | 204 |
| PATCH | `api/publish` | Publish recursively | 204 |
| PATCH | `api/unpublish` | Unpublish element | 204 |
| DELETE | `api/delete` | Archive element | 204 |
| POST | `api/duplicate` | Duplicate element | 204 |
| PATCH | `api/reorder` | Reorder/move element | 204 |

All mutations return 204 (no body) on success. The frontend refetches the tree after each mutation to reconcile state.

### Request Validation

The controller validates request bodies with typed parsing methods that return PHPStan-typed arrays:

- `parseCreateBody()` — validates `elementClass` (must be `GridElement` subclass), `parentId`, `parentClass`, `insertAfterElementID`, `zone`
- `parseReorderBody()` — validates `elementID`, `targetParentId`, `afterElementID`
- `requireElementId()` — validates a single `id` field

Invalid payloads produce HTTP 400. Validation failures from the service layer produce HTTP 422 with structured error JSON.

### Permission Model

| Check | Applies to |
|-------|-----------|
| CSRF token | All mutations |
| `canView()` on page | Tree reads |
| `canEdit()` on parent | Create, reorder (target parent) |
| `canCreate()` on element | Create, duplicate |
| `canEdit()` on element | Reorder |
| `canEdit()` on source parent | Cross-parent reorder |
| `canDelete()` on element | Delete |
| `canPublish()` / `canUnpublish()` | Publish / unpublish |

Permission resolution delegates to the owning page: `GridElement.canEdit()` walks the parent chain to the nearest `SiteTree` and calls `canEdit()` on it. Orphaned elements (no page in chain) fall back to `CMS_ACCESS` permission.

### Client Configuration

`getClientConfig()` exposes the grid adapter configuration to the frontend via SilverStripe's admin client config mechanism:

```php
[
    'viewports'        => [['key' => 'xs', 'label' => 'Extra small'], ...],
    'defaultViewport'  => 'md',
    'columnCount'      => 12,
    'rowClasses'       => 'row',
    'baseWidthClasses' => {'1': 'col-1', '2': 'col-2', ...},
    'baseOffsetClasses'=> {'0': 'offset-0', '1': 'offset-1', ...},
]
```

This allows the frontend grid editor to render column width previews and viewport controls without knowing the concrete CSS framework.

## Service Layer

### GridTreeBuilder

Builds the full element tree for a page using breadth-first batch loading — one query per hierarchy depth level.

```
buildForPage(page, zone)
  │
  ├─ loadAllElements()
  │    ├─ Level 0: query Sections by page (zone-filtered)
  │    ├─ Level 1: query Rows by all Section IDs
  │    ├─ Level 2: query Columns by all Row IDs
  │    └─ Level 3: query content elements by all Column IDs
  │
  └─ assembleSubTree()
       └─ Recursive in-memory assembly from pre-loaded data
```

Elements are keyed by the composite `"ParentClass:ParentID"` string in the lookup map. This prevents false matches when a page ID coincides with an element ID.

Each element is converted to a `GridNode` DTO — a readonly value object that carries base fields (id, parentId, title, blockSchema, version, permissions, statusFlags) and optional container fields (containerType, allowedTypes, children). Column nodes additionally carry `gridSettings`. The `GridNode` implements `JsonSerializable` with conditional field inclusion: leaf nodes omit container fields from the serialized output.

The builder provides an `updateElementData` extension point, allowing other modules to inject additional data into each node's `extensions` array.

### ReorderService

Orchestrates element reordering through three phases:

```
reorder(element, targetParent, afterElementId)
  │
  ├─ Phase 1: ReorderValidator.validate()
  │    └─ Hierarchy rule check (cross-parent only)
  │
  ├─ Phase 2: ReorderExecutor.execute()
  │    └─ Sort calculation (in-memory, no writes)
  │
  └─ Phase 3: ElementPersistenceService.persistBatch()
       └─ Write only dirty elements
```

Each phase returns a `Result`. If any phase fails, the pipeline short-circuits and the failure propagates to the controller.

### ReorderExecutor

Computes new Sort values entirely in memory:

1. Load target siblings (zone-filtered for Sections)
2. Exclude the moved element from the sibling list
3. Resolve insertion index from `afterElementId` (`null` means first position)
4. `array_splice()` the element into position
5. Reindex Sort values (1-based: 1, 2, 3, ...)
6. Track dirty elements (only those whose `Sort` or `ParentID` actually changed)

For cross-parent moves, the source parent's siblings are also reindexed to close the gap left by the moved element. The moved element is marked always-dirty even if its Sort value happens to stay the same, because its `ParentID` has changed.

### ElementPersistenceService

The single boundary where SilverStripe's `ValidationException` becomes a domain `Result`. Three write operations:

| Method | Use case |
|--------|----------|
| `persistNew()` | Create element, optionally insert after reference |
| `persistDuplicate()` | Duplicate element, insert after original |
| `persistBatch()` | Write multiple elements (reorder), stop on first failure |

The `insertAfter()` helper bumps Sort values of downstream siblings to make room for the new element.

## Validation Layer

Hierarchy validation runs in two contexts with shared logic:

### Write-Time: HierarchyValidationExtension

Applied globally to `GridElement` via YAML. Hooks into `updateValidate()` in the SilverStripe write lifecycle:

1. No parent (orphan) → pass
2. Parent is `SiteTree` → check `can_be_root` on the element
3. Parent is container → check `isElementAllowed()` against config

Violations throw `ValidationException`, preventing the database write.

### Reorder-Time: ReorderValidator

Called by `ReorderService` before executing a cross-parent move. Applies the same hierarchy rules but returns `Result::fail()` instead of throwing, maintaining the Result pattern contract.

Same-parent moves skip validation entirely — reordering within a container cannot violate hierarchy rules.

### ElementAllowanceTrait

Shared logic for checking whether an element class is permitted by a parent's `allowed_elements` / `disallowed_elements` config. Respects the `stop_element_inheritance` flag to prevent config inheritance up the class hierarchy. Used by both `HierarchyValidationService` and `ReorderValidator`.

## Repository Layer

`GridElementRepositoryInterface` provides three query methods:

| Method | Purpose |
|--------|---------|
| `findById(int)` | Single element lookup |
| `findByParentIds(list<int>)` | Elements by ParentID (simple filter) |
| `findByParents(array<class, list<int>>, ?zone)` | Composite key + optional zone filter |

`OrmGridElementRepository` implements these against the SilverStripe ORM. All queries sort by `Sort ASC, ID ASC`. Zone filtering queries the `Section` table directly (the `Zone` column only exists there).

## Result Pattern

Service-layer operations return `Result<T>` for expected validation failures. Exceptions are reserved for programming errors and infrastructure failures.

```php
Result::ok($value)              // Success, carries the value
Result::fail($error, ...$rest)  // Failure, carries ValidationError[]
```

| Method | Purpose |
|--------|---------|
| `isOk()` / `isErr()` | Check outcome |
| `unwrap()` | Access success value (throws on failure — programmer bug) |
| `errors()` | Access validation errors |
| `map(fn)` | Transform success value, no-op on failure |

The controller maps `Result::ok()` to HTTP 204 and `Result::fail()` to HTTP 422 with error messages.

## Grid Adapter System

Grid adapters translate the abstract layout model (viewports, column widths, offsets, visibility) into CSS framework-specific class names. All consumers depend on `GridAdapterInterface`, never on a concrete adapter.

### Interface Contract (12 methods)

| Method | Returns | Purpose |
|--------|---------|---------|
| `getViewports()` | `list<Viewport>` | Active viewport breakpoints |
| `getColumnCount()` | `positive-int` | Total grid columns |
| `getDefaultViewport()` | `Viewport` | Default/base viewport |
| `getWidthClass(viewport, width)` | `string` | Width class for viewport |
| `getOffsetClass(viewport, offset)` | `string` | Offset class for viewport |
| `getBaseWidthClass(width)` | `string` | Width class for base viewport |
| `getBaseOffsetClass(offset)` | `string` | Offset class for base viewport |
| `getVisibilityClasses(viewport)` | `list<string>` | Hide/restore class pair |
| `getRowClasses()` | `string` | Row container classes |
| `getContainerClass(fluid)` | `string` | Container wrapper classes |
| `getTitleClassOptions()` | `array<string, string>` | CSS class to label mapping |
| `getCssPath()` | `?string` | Path to bundled CSS, or null |

### Configuration Trait

`GridAdapterConfiguration` provides three YAML-configurable properties applied to any adapter:

| Property | Type | Effect |
|----------|------|--------|
| `enabled_viewports` | `list<string>\|null` | Restrict active viewports |
| `total_columns` | `int\|null` | Override column count |
| `default_viewport` | `string\|null` | Override default viewport |

The trait provides helper methods (`applyViewportFilter`, `resolveColumnCount`, `resolveDefaultViewport`) called in the adapter constructor. Invalid configuration throws `InvalidGridValueException`.

### Adapters

| Adapter | Viewports | Base viewport | Width pattern |
|---------|-----------|--------------|---------------|
| Bootstrap | xs, sm, md, lg, xl, xxl | xs (no infix) | `col-{vp}-{n}` |
| Tailwind | sm, md, lg, xl, 2xl | sm | `{vp}:col-span-{n}` |
| Bulma | mobile, tablet, desktop, widescreen, fullhd | mobile (no suffix) | `is-{n}-{vp}` |

The default adapter is Bootstrap, bound via YAML DI:

```yaml
SilverStripe\Core\Injector\Injector:
  WeDevelop\Grid\Contract\GridAdapterInterface:
    class: WeDevelop\Grid\Adapter\BootstrapAdapter
```

## Value Objects

| Class | Purpose |
|-------|---------|
| `ContainerType` | Enum: Section, Row, Column |
| `Viewport` | `final readonly class` with `key` and `label` |
| `GridNode` | Readonly DTO for serialized tree nodes |
| `Result<T>` | Generic success/failure container |
| `ValidationError` | Structured error with message, field, severity |
| `ValidationSeverity` | Enum: Error, Warning |

## Dependency Injection

Two injection styles coexist due to SilverStripe framework constraints:

**Property injection** (`$dependencies` array) — used by controllers and elements because the framework instantiates them without DI arguments:

```php
private static array $dependencies = [
    'gridAdapter' => '%$' . GridAdapterInterface::class,
];
public GridAdapterInterface $gridAdapter;
```

**Constructor injection** — used by services, with explicit `constructor:` config in YAML because the Injector does not auto-wire constructor parameters from interface bindings:

```yaml
WeDevelop\Grid\Service\ReorderService:
  constructor:
    validator: '%$WeDevelop\Grid\Contract\ReorderValidatorInterface'
    executor: '%$WeDevelop\Grid\Contract\ReorderExecutorInterface'
    persistenceService: '%$WeDevelop\Grid\Service\ElementPersistenceService'
```

## CMS Integration

`GridPageExtension` (applied to `SiteTree` via YAML) provides the integration point:

- Declares `has_many` to Section (with `owns`, `cascade_deletes`, `cascade_duplicates`)
- Removes the default `Content` field from the CMS form
- Injects `GridEditorField` as the React mount point for the grid editor

`GridEditorField` is a lightweight `FormField` subclass that renders data attributes (`pageId`, `zone`) and delegates all mutations to the API controller. Its `saveInto()` is a no-op — the grid editor manages persistence through the JSON API, not through the CMS form save cycle.

## Extension Points

| Hook | Location | Purpose |
|------|----------|---------|
| `updateContainerClasses` | Section | Modify container CSS classes |
| `updateColumnClasses` | Column | Modify column CSS classes |
| `updateElementData` | GridTreeBuilder | Inject extra data into tree nodes |
| `extendedCan` | GridElement | Override permission checks |
| `updateValidate` | HierarchyValidationExtension | Intercept validation lifecycle |
