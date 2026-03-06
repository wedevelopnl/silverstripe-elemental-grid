---
description: Element hierarchy rules, auto-scaffolding behavior, and validation logic
applyTo: "**/*"
---

# Element Hierarchy

## Structure

The grid enforces a strict three-level hierarchy using polymorphic parent relationships (ParentID + ParentClass):

```
Page (SiteTree)
  └── Section   [ContainerType::Section]    — can_be_root: true (default), zone-scoped
        └── Row   [ContainerType::Row]       — can_be_root: false
              └── Column  [ContainerType::Column]  — can_be_root: false, stores GridSettings JSON
                    └── (any non-container content element)
```

All three container elements implement `ContainerInterface`:
- `getChildren(): HasManyList<GridElement>`
- `hasChildren(): bool`
- `getContainerType(): ContainerType`

Container behavior is shared via `ContainerElementTrait`.

## Hierarchy Rules (YAML Config)

- **Section**: `allowed_elements: [Row]` — only Rows as children
- **Row**: `allowed_elements: [Column]`, `can_be_root: false` — only Columns as children, cannot be placed at page level
- **Column**: `disallowed_elements: [Section, Row, Column]`, `can_be_root: false` — blocklist approach, allows any non-container content element

## Parent Relationships

Elements use a polymorphic `has_one` (`ParentID + ParentClass`) to link to any DataObject:
- Section → parent is `SiteTree` (page)
- Row → parent is `Section`
- Column → parent is `Row`
- Content element → parent is `Column`

Page IDs and element IDs share no namespace separation, so lookup maps must key by composite `"ParentClass:ParentID"` strings.

## Zones

Sections carry a `Zone` field (e.g., `"main"`, `"sidebar"`) scoping them within a page. Sort values are independent per zone per parent. All queries (tree loading, sort assignment, reorder) filter by zone at the root level.

## Auto-Scaffolding

Writing a container element automatically creates its required child structure on DRAFT stage.

### Cascade Chain

1. `Section::onAfterWrite()` → creates a `Row` if no children exist
2. `Row::onAfterWrite()` → creates a `Column` if no children exist
3. `Column` does NOT auto-scaffold (only initializes `GridSettings` JSON on first write)

**Result**: A single `Section::create()->write()` produces the full `Section → Row → Column` tree.

### Guard Conditions (Idempotency)

Both Section and Row check before scaffolding:
1. `Versioned::get_stage() === Versioned::DRAFT` — no scaffolding on LIVE
2. `$this->getChildren()->count() > 0` — no scaffolding if children already exist

Auto-scaffolding can be disabled per class via `auto_scaffold: false` in YAML. Subsequent writes to the same element do NOT create duplicate children.

### Configurable Default Titles

- `Section::$default_row_title` (default: `''`)
- `Row::$default_column_title` (default: `''`)

## Hierarchy Validation

Validation happens in two contexts with shared logic via `ElementAllowanceTrait`.

### At Write Time: `HierarchyValidationExtension`

Applied globally to `GridElement` via YAML. Hooks into `updateValidate()`:

1. No parent → pass (orphan)
2. Parent is a SiteTree page → check `can_be_root` on the element
3. Parent is a container → check `isElementAllowed()` against `allowed_elements`/`disallowed_elements`

Violation throws `ValidationException`, preventing the database write.

### At Reorder Time: `ReorderValidator`

Called by `ReorderService` before executing a cross-parent move:

1. Same-parent move → always `Result::ok()` (no hierarchy change)
2. Cross-parent move → applies the same `can_be_root` and `isElementAllowed()` checks
3. Returns `Result::fail()` for violations (uses Result pattern, not exceptions)

## Integration Test Implications

### Auto-Scaffolding Awareness

Tests creating container elements **must** account for auto-scaffolded children:

```php
// Creating a Section produces Section + Row + Column (3 elements total)
$section = Section::create();
$section->ParentID = $page->ID;
$section->ParentClass = $page::class;
$section->write();

// The section now has 1 Row child
$this->assertCount(1, $section->getChildren());

// That Row has 1 Column child
$row = $section->getChildren()->first();
$this->assertCount(1, $row->getChildren());
```

### Stage Setup Required

All container integration tests must call `Versioned::set_stage(Versioned::DRAFT)` in `setUp()` because `FlushableTestState::setUp()` clears the reading mode, which would break scaffolding hooks.

## E2E Fixture Ordering

YAML fixtures must list elements **bottom-up** (leaf → column → row → section → page) to prevent auto-scaffolding from creating duplicate children. See `e2e-conventions` instructions for details.
