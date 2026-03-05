# Design: Remove dnadesign/silverstripe-elemental Dependency

**Date**: 2026-03-05
**Status**: Draft — awaiting review

## Motivation

The current module (`wedevelopnl/silverstripe-elemental-grid`) depends on `dnadesign/silverstripe-elemental` despite being a ground-up rewrite for SilverStripe 6. The dependency is a legacy artifact from the SS5 version where we extended elemental. In the SS6 version, we only truly use `BaseElement` (as a base class) and `ElementalArea` (as a container join model), while actively disabling most of elemental's functionality.

### Problems with the current approach

1. **Technical debt** — dragging an unused dependency increases surface area, bundle size, and complexity.
2. **Upstream coupling** — any change to elemental's `BaseElement`, extensions, or services can break our module. We must track elemental development indefinitely.
3. **Design constraints** — every architectural decision must account for elemental compatibility, limiting our ability to build the right abstractions.
4. **Active suppression** — we override `ElementalAreaField` via Injector, disable elemental's admin UI, and work around its assumptions. This "disable layer" is fragile and confusing.

### Decision

Completely remove the `dnadesign/silverstripe-elemental` dependency and declare it as a conflict. Rewrite the base classes as clean-sheet implementations designed for the grid's domain model. Defer any compatibility shim/polyfill for third-party elemental blocks to a future release when a concrete use case arises.

## Package Rename

As part of this change, the package identity is updated to reflect independence from elemental:

| Aspect | Current | New |
|--------|---------|-----|
| Package name | `wedevelopnl/silverstripe-elemental-grid` | `wedevelopnl/silverstripe-grid` |
| PHP namespace | `WeDevelop\ElementalGrid\` | `WeDevelop\Grid\` |
| PSR-4 mapping | `WeDevelop\\ElementalGrid\\` → `src/` | `WeDevelop\\Grid\\` → `src/` |

## Class Hierarchy

### Current (elemental-dependent)

```
DNADesign\Elemental\Models\BaseElement (vendor)
  ├── ElementSection
  ├── ElementRow
  └── ElementColumn

DNADesign\Elemental\Models\ElementalArea (vendor, intermediary join model)
  └── has_many → BaseElement
```

All three container elements and all content elements share the same base class. `ElementalArea` sits between every parent-child relationship as a join model.

### Proposed (clean-sheet)

```
WeDevelop\Grid\Model\GridElement (abstract DataObject)
  ├── Schema: Title, Sort, ShowTitle, ExtraClass, Style
  ├── Polymorphic has_one Parent → DataObject
  ├── Zone (Varchar) — identifies which GridEditorField this root element belongs to
  ├── Versioned, permissions (delegated to page via parent chain walk)
  ├── getType(), CMS edit linking, sort management
  │
  ├── WeDevelop\Grid\Model\ContainerElement (abstract)
  │   ├── Implements ContainerInterface
  │   ├── Typed has_many to children
  │   ├── $owns, $cascade_deletes, $cascade_duplicates on children
  │   ├── Hierarchy rules (allowed children, can_be_root — YAML configured)
  │   ├── Auto-scaffolding on write
  │   │
  │   ├── Section (has_many Rows → Row.Parent)
  │   ├── Row (has_many Columns → Column.Parent)
  │   └── Column (has_many Elements → ContentElement.Parent)
  │
  └── WeDevelop\Grid\Model\ContentElement (abstract)
      ├── forTemplate(), anchor generation
      ├── Search indexing, editor preview
      └── Custom content blocks extend this
```

### Key design decisions

**1. Two abstract branches, not one base class.**
Containers (structural, have children, define layout) and content elements (presentational, leaf nodes, render content) are fundamentally different domain concepts. The shared `BaseElement` in elemental conflates these because elemental has no hierarchy concept. Our grid does, so the class hierarchy should reflect the domain.

**2. No intermediary "area" model.**
`ElementalArea` exists in elemental as a join model between a parent (Page or container) and its child elements. With polymorphic `has_one Parent` on `GridElement`, parent-child relationships are direct:
- `Section.has_many Rows` → `Row` where `ParentID = Section.ID AND ParentClass = Section`
- `Row.has_many Columns` → `Column` where `ParentID = Row.ID AND ParentClass = Row`
- `Column.has_many Elements` → `ContentElement` where `ParentID = Column.ID AND ParentClass = Column`
- Page `has_many Sections` → `Section` where `ParentID = Page.ID AND ParentClass = Page`

Benefits:
- One fewer model to maintain
- Direct relations: `$section->Rows()` instead of `$section->ChildArea()->Elements()`
- Simpler page resolution: walk up `Parent` chain (fixed depth, max 3 hops)
- `$owns`/`$cascade_deletes` on the container itself — explicit and obvious

**3. Multiple zones via `Zone` field, not multiple areas.**
A page can have multiple `GridEditorField` instances (e.g., "MainContent", "Sidebar"). Each field represents a zone. Root elements (Sections) have a `Zone` varchar field tagging which `GridEditorField` they belong to. The field queries by `ParentID + ParentClass + Zone`.

This is analogous to how a page can have multiple `TextField`s — no intermediary model needed, just multiple form fields each managing their own data slice.

**4. Page ownership via single `has_many`.**
For Versioned `$owns` and `$cascade_deletes`, the page extension declares a single `has_many` covering all root grid elements (regardless of zone). Zone is purely a UI/query concern, not a data ownership concern.

## Class Naming

All "Element/Elemental" prefixes are removed:

| Current | Proposed |
|---------|----------|
| `ElementSection` | `Section` |
| `ElementRow` | `Row` |
| `ElementColumn` | `Column` |
| `ElementContainerInterface` | `ContainerInterface` |
| `ElementalGridController` | `GridController` |
| `ElementTreeBuilder` | `GridTreeBuilder` |
| `ElementNode` (DTO) | `GridNode` |
| `ElementPersistenceService` | `PersistenceService` |
| `ElementRepositoryInterface` | `GridElementRepositoryInterface` |
| `OrmElementRepository` | `OrmGridElementRepository` |
| `ElementalAreaRepositoryInterface` | Eliminated (no area model) |
| `OrmElementalAreaRepository` | Eliminated (no area model) |
| `HierarchyValidationExtension` | `HierarchyValidationExtension` (unchanged) |
| `HierarchyValidationService` | `HierarchyValidationService` (unchanged) |
| `GridEditorField` | `GridEditorField` (unchanged) |
| `ReorderExecutor` | `ReorderExecutor` (unchanged) |
| `ReorderValidator` | `ReorderValidator` (unchanged) |
| `ReorderService` | `ReorderService` (unchanged) |

## What Gets Eliminated

### From elemental (no longer needed)

| Component | Reason |
|-----------|--------|
| `DNADesign\Elemental\Models\BaseElement` | Replaced by `GridElement` |
| `DNADesign\Elemental\Models\ElementalArea` | Eliminated — direct parent-child relations |
| `DNADesign\Elemental\Extensions\ElementalAreasExtension` | Replaced by our own page extension |
| `DNADesign\Elemental\Extensions\ElementalPageExtension` | Replaced — parent chain walk removes need for `getElementalRelations()` |
| `DNADesign\Elemental\Services\ReorderElements` | Already wrapped by `ReorderExecutor` |
| `DNADesign\Elemental\Forms\ElementalAreaField` | `GridEditorField` no longer injected as replacement — it IS the field |
| All elemental controllers, GraphQL, admin UI | Never used |

### From our codebase

| Component | Reason |
|-----------|--------|
| `OrmElementalAreaRepository` | No area model to query |
| `ElementalAreaRepositoryInterface` | No area model to query |
| Injector override for `ElementalAreaField` | No longer needed (`_config/cms.yml`) |
| `ElementalAreasExtension` application in YAML | Replaced by our own extension |

## What Gets Reimplemented

These capabilities from `BaseElement`/`ElementalArea` are reimplemented in `GridElement`:

| Capability | Implementation |
|-----------|----------------|
| DB schema (Title, Sort, etc.) | `GridElement` `$db` and `$has_one` |
| Permission delegation to page | Walk polymorphic `Parent` chain to find owning Page |
| Sort management (`ensureSortSet`) | On `GridElement`, scoped to `ParentID + ParentClass` |
| Versioned publishing cascade | `$owns` on each container pointing to its typed children |
| CMS edit linking | On `GridElement`, adapted for direct parent chain |
| `forTemplate()` / template rendering | On `ContentElement` |
| Anchor generation | On `ContentElement` |
| Search indexing | On `ContentElement` |
| `getType()` / block type info | On `GridElement` |

## Composer Changes

```json
{
  "name": "wedevelopnl/silverstripe-grid",
  "require": {
    "silverstripe/framework": "^6.0",
    "silverstripe/admin": "^3.0",
    "silverstripe/vendor-plugin": "^3.0",
    "silverstripe/versioned": "^3.0",
    "silverstripe/cms": "^6.0"
  },
  "conflict": {
    "dnadesign/silverstripe-elemental": "*"
  },
  "autoload": {
    "psr-4": {
      "WeDevelop\\Grid\\": "src/"
    }
  }
}
```

Notes:
- `dnadesign/silverstripe-elemental` moves from `require` to `conflict`
- `dnadesign/silverstripe-elemental-list` conflict is removed — it's implicit (elemental-list requires elemental, which is now conflicted)
- PSR-4 namespace updated

## Page Integration

### New page extension

Replaces `ElementalAreasExtension` and `ElementalPageExtension`. Applied to `SiteTree` (or specific page types) via YAML.

Responsibilities:
- Adds `has_many` relation from Page to root grid elements (Sections)
- Provides `$owns` and `$cascade_deletes` for the relation
- Injects `GridEditorField` into the page's CMS fields
- Supports multiple zones: one `GridEditorField` per zone, each querying by `Zone` field

### GridEditorField changes

- No longer receives an `ElementalArea` — receives the page and zone name instead
- Queries root elements directly: `Section::get()->filter(['ParentID' => $page->ID, 'ParentClass' => $page->ClassName, 'Zone' => $this->zoneName])`
- Schema data provides `grid-page-id` and `grid-zone` instead of `grid-area-id`

## Frontend Impact

### API changes

The `GridController` endpoints currently reference area IDs. These change to page ID + zone:
- Element tree endpoint: `pageId` + `zone` instead of `areaId`
- Mutation endpoints: `parentId` + `parentClass` instead of `areaId`

### Type changes

- `ElementNode` → `GridNode` — schema updates in Zod types
- Remove any `areaId` references from API types
- `parentId` field on nodes now references the actual parent (container or page), not an area

### Component changes

- Bridge/entwine reads `grid-page-id` and `grid-zone` from schema data instead of `grid-area-id`
- Query key factories update to use `pageId + zone` instead of `areaId`
- Minimal impact on React components — they already work with the tree structure, not areas directly

## Migration Path

This is a **breaking change**. Since the SS6 version is a ground-up rewrite on an orphaned branch with no production installations, no automated migration is provided:

- New table `GridElement` (or appropriate name) replaces `Element`
- `ParentID` + `ParentClass` columns replace the `ElementalArea` join
- `Zone` column added for multi-zone support
- `ElementalArea` table no longer used

## Future: Compatibility Shim (Deferred)

A potential future package (`wedevelop/silverstripe-elemental-compat` or similar) could:
- Provide `DNADesign\Elemental\Models\BaseElement` extending `WeDevelop\Grid\Model\ContentElement`
- Provide `DNADesign\Elemental\Models\ElementalArea` as a thin wrapper
- Use Composer `replace` to satisfy third-party block dependencies on elemental
- Allow third-party elemental blocks to work within the grid without code changes

This is explicitly **not part of this change**. It will be designed and implemented when a concrete use case arises.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Third-party elemental blocks incompatible | Cannot use blocks from elemental ecosystem | Accepted trade-off; shim deferred to future release |
| Polymorphic `has_one` performance | Additional `ParentClass` column in queries | Fixed-depth hierarchy (max 3 hops); index on `ParentID + ParentClass` |
| Multiple zones complexity | Zone field adds a concept to the data model | Simple varchar field; defaults to single zone for most sites |
| Large refactor scope | Many files touched across PHP and frontend | Comprehensive test suite (unit, integration, E2E) provides safety net |

## Test Strategy

- **Existing tests** are the primary safety net — all must pass after refactoring
- **Unit tests**: Update imports/namespaces, verify `GridElement`/`ContainerElement`/`ContentElement` behavior
- **Integration tests**: Verify hierarchy scaffolding, permissions, versioned cascading, sort management — all without `ElementalArea`
- **E2E tests**: Verify full user flows still work with updated API contracts
- **New tests**: Polymorphic parent chain walk for page resolution, zone-based querying
