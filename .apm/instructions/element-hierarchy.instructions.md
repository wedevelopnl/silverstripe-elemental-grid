---
description: Element hierarchy rules, auto-scaffolding behavior, and validation logic
applyTo: "**/*"
---

# Element Hierarchy

## Structure

The grid enforces a strict three-level hierarchy:

```
Page (ElementalArea)
  └── ElementSection   [ContainerType::Section]    — can_be_root: true (default)
        └── ElementRow   [ContainerType::Row]       — can_be_root: false
              └── ElementColumn  [ContainerType::Column]  — can_be_root: false
                    └── (any non-container content element)
```

All three container elements implement `ElementContainerInterface`:
- `getChildArea(): ElementalArea`
- `hasChildren(): bool`
- `getContainerType(): ContainerType`

## Hierarchy Rules (YAML Config)

- **ElementSection**: `allowed_elements: [ElementRow]` — only Rows as children
- **ElementRow**: `allowed_elements: [ElementColumn]`, `can_be_root: false` — only Columns as children, cannot be placed at page level
- **ElementColumn**: `disallowed_elements: [ElementSection, ElementRow, ElementColumn]`, `can_be_root: false` — blacklist approach, allows any non-container content element

## Auto-Scaffolding

Writing a container element automatically creates its required child structure on DRAFT stage.

### Cascade Chain

1. `ElementSection::onAfterWrite()` → creates an `ElementRow` if `ChildArea` is empty
2. `ElementRow::onAfterWrite()` → creates an `ElementColumn` if `ChildArea` is empty
3. `ElementColumn` does NOT auto-scaffold (only initializes `GridSettings` JSON on first write)

**Result**: A single `ElementSection::create()->write()` produces the full `Section → Row → Column` tree.

### Guard Conditions (Idempotency)

Both Section and Row check before scaffolding:
1. `Versioned::get_stage() === Versioned::DRAFT` — no scaffolding on LIVE
2. `$childArea->Elements()->count() > 0` — no scaffolding if children already exist

Subsequent writes to the same element do NOT create duplicate children.

### Configurable Default Titles

- `ElementSection::$default_row_title` (default: `''`)
- `ElementRow::$default_column_title` (default: `''`)

## Hierarchy Validation

Validation happens in two contexts with partially duplicated logic.

### At Write Time: `HierarchyValidationExtension`

Applied globally to all `BaseElement` subclasses via YAML. Hooks into `updateValidate()`:

1. No parent → pass (root-level orphan)
2. Parent area has no owner → pass (orphaned area)
3. Owner is a SiteTree page → check `can_be_root` on the element
4. Otherwise → check `isElementAllowed()` against `allowed_elements`/`disallowed_elements`

Violation throws `ValidationException`, preventing the database write.

### At Reorder Time: `ReorderValidator`

Called by `ReorderService` before executing a cross-area move:

1. Same-area move → always `Result::ok()` (no hierarchy change)
2. Cross-area move → applies the same `can_be_root` and `isElementAllowed()` checks
3. Returns `Result::fail()` for violations (uses Result pattern, not exceptions)

**Known tech debt**: `isElementAllowed()` is duplicated identically in both `HierarchyValidationService` and `ReorderValidator` (not shared via trait or base class).

## Integration Test Implications

### Auto-Scaffolding Awareness

Tests creating container elements **must** account for auto-scaffolded children:

```php
// Creating a Section produces Section + Row + Column (3 elements total)
$section = ElementSection::create();
$section->ParentID = $area->ID;
$section->write();

// The child area now has 1 Row
$this->assertCount(1, $section->getChildArea()->Elements());

// That Row's child area has 1 Column
$row = $section->getChildArea()->Elements()->first();
$this->assertCount(1, $row->getChildArea()->Elements());
```

### Stage Setup Required

All container integration tests must call `Versioned::set_stage(Versioned::DRAFT)` in `setUp()` because `FlushableTestState::setUp()` clears the reading mode, which would break scaffolding hooks.

## E2E Fixture Ordering

YAML fixtures must list elements **bottom-up** (leaf → column → row → section → page) to prevent auto-scaffolding from creating duplicate children. See `e2e-conventions` instructions for details.
