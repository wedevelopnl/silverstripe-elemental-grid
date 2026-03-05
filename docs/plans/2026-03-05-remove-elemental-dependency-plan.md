# Remove Elemental Dependency — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove `dnadesign/silverstripe-elemental` dependency entirely, replacing it with clean-sheet base classes, direct parent-child relationships (no intermediary area model), and a full package/namespace rename.

**Architecture:** `GridElement` (abstract) branches into `ContainerElement` (abstract, for Section/Row/Column) and `ContentElement` (abstract, for content blocks). Polymorphic `has_one Parent → DataObject` replaces the `ElementalArea` join model. Package becomes `wedevelopnl/silverstripe-grid` with namespace `WeDevelop\Grid\`.

**Tech Stack:** PHP 8.3, SilverStripe 6, React 18, TypeScript 5.9, Vite 7, Vitest, PHPUnit 11, Playwright

**Design doc:** `docs/plans/2026-03-05-remove-elemental-dependency-design.md`

---

## Overview of Phases

This plan is structured in 7 phases. Each phase produces a committable, internally consistent state. Phases MUST be executed in order.

1. **Phase 1: Package & namespace rename** — composer.json, PSR-4, all `WeDevelop\ElementalGrid` → `WeDevelop\Grid`
2. **Phase 2: Base class hierarchy** — Create `GridElement`, `ContainerElement`, `ContentElement` abstract classes
3. **Phase 3: Eliminate ElementalArea** — Polymorphic `has_one Parent`, remove area repositories, update container elements
4. **Phase 4: Remove elemental imports** — Replace all remaining `DNADesign\Elemental` references
5. **Phase 5: Service & controller refactor** — Update tree builder, reorder, persistence, controller for new model
6. **Phase 6: Frontend contract update** — Update Zod schemas, API endpoints, type definitions
7. **Phase 7: YAML config, templates, cleanup** — Config files, template paths, class renames, final cleanup

**Important:** Each phase has many files to touch. Run `make analyse` (PHPStan) and `npm run typecheck` after each phase to catch regressions early. Run full test suites (`make test` + `npm run test`) after phases 2-7.

---

## Phase 1: Package & Namespace Rename

This phase is purely mechanical — find-and-replace across the entire codebase. No structural changes.

### Task 1.1: Update composer.json

**Files:**
- Modify: `composer.json`

**Step 1: Update package identity and dependencies**

Change the following in `composer.json`:
- `"name"` → `"wedevelopnl/silverstripe-grid"`
- `"description"` → `"Grid-based content block system for SilverStripe"`
- `"keywords"` → `["silverstripe", "content blocks", "grid", "layout"]`
- Remove `"dnadesign/silverstripe-elemental": "^6.0"` from `require`
- Add `"silverstripe/versioned": "^3.0"` and `"silverstripe/cms": "^6.0"` to `require` (currently transitive via elemental)
- Change `"conflict"` to: `{"dnadesign/silverstripe-elemental": "*"}`
- Update `"autoload"` PSR-4: `"WeDevelop\\Grid\\"` → `"src/"`
- Update `"autoload-dev"` PSR-4: `"WeDevelop\\Grid\\Tests\\"` → `"tests/"`

**Step 2: Verify composer validates**

Run: `composer validate --strict`
Expected: No errors

**Step 3: Commit**

```bash
git add composer.json
git commit -m "chore: rename package to wedevelopnl/silverstripe-grid, remove elemental dependency"
```

### Task 1.2: Update package.json and vite.config.ts

**Files:**
- Modify: `package.json`
- Modify: `vite.config.ts`

**Step 1: Update package.json name**

Change `"name"` from `"@wedevelopnl/silverstripe-elemental-grid"` to `"@wedevelopnl/silverstripe-grid"`.

**Step 2: Update vite.config.ts lib name (if present)**

If the build output references "ElementalGrid" as a library name, change to "Grid".

**Step 3: Commit**

```bash
git add package.json vite.config.ts
git commit -m "chore: rename frontend package to @wedevelopnl/silverstripe-grid"
```

### Task 1.3: Rename all PHP namespaces

**Files:**
- Modify: ALL files in `src/` and `tests/` (~80+ files)

**Step 1: Find-and-replace namespace declarations and imports**

Across all `.php` files in `src/` and `tests/`:
- Replace `WeDevelop\ElementalGrid\` with `WeDevelop\Grid\`
- Replace `WeDevelop\\ElementalGrid\\` with `WeDevelop\\Grid\\` (in strings/YAML references within PHP)

This is a bulk operation. Use a reliable find-and-replace tool. Verify no partial matches (e.g., don't accidentally replace inside vendor/).

**Step 2: Run PHPStan to verify namespace consistency**

Run: `make analyse`
Expected: May show errors related to elemental imports (those are fixed in later phases), but no "class not found" errors for our own classes.

**Step 3: Commit**

```bash
git add src/ tests/
git commit -m "refactor: rename namespace WeDevelop\ElementalGrid to WeDevelop\Grid"
```

### Task 1.4: Update YAML config files

**Files:**
- Modify: `_config/elements.yml`
- Modify: `_config/cms.yml`
- Modify: `_config/grid.yml`
- Modify: `_config/dev.yml`

**Step 1: Find-and-replace in all YAML files**

Replace all occurrences of:
- `WeDevelop\ElementalGrid\` → `WeDevelop\Grid\`
- `wedevelopnl/silverstripe-elemental-grid:` → `wedevelopnl/silverstripe-grid:`

**Step 2: Commit**

```bash
git add _config/
git commit -m "refactor: update YAML config to new namespace and package name"
```

### Task 1.5: Update Docker composer.json (if exists)

**Files:**
- Modify: `.docker/app/composer.json` (if it references the package name or namespace)

**Step 1: Update any references to old package/namespace**

**Step 2: Commit**

```bash
git add .docker/
git commit -m "chore: update Docker config for new package name"
```

### Task 1.6: Update fixture YAML files

**Files:**
- Modify: All `.yml` files in `tests/E2E/Fixture/` and `tests/Integration/Fixture/`

**Step 1: Replace class references**

Replace `WeDevelop\ElementalGrid\Elements\Element` → `WeDevelop\Grid\Elements\Element` (Note: the Element→Section/Row/Column rename happens in Phase 7, not here).

**Step 2: Commit**

```bash
git add tests/
git commit -m "refactor: update test fixture class paths to new namespace"
```

---

## Phase 2: Base Class Hierarchy

Create the three abstract base classes that replace `BaseElement`.

### Task 2.1: Create GridElement abstract base class

**Files:**
- Create: `src/Model/GridElement.php`

**Step 1: Write GridElement**

This is the core abstract DataObject that all grid elements extend. It replaces `BaseElement` as the inheritance root.

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * Abstract base for all elements in the grid hierarchy.
 *
 * Provides the shared schema, versioning, permissions, anchoring,
 * and parent-chain traversal. Extended by ContainerElement (structural)
 * and ContentElement (presentational).
 *
 * @property string $Title
 * @property bool $ShowTitle
 * @property int $Sort
 * @property string $ExtraClass
 * @property string $Style
 * @property string $Zone
 * @property int $ParentID
 * @property string $ParentClass
 *
 * @mixin Versioned
 */
abstract class GridElement extends DataObject
{
    private static string $table_name = 'GridElement';

    private static string $singular_name = 'block';

    private static string $plural_name = 'blocks';

    private static string $default_sort = 'Sort';

    private static string $icon = 'font-icon-block-layout';

    private static string $class_description = 'Base grid element';

    /** @var array<string, string> */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'ShowTitle' => 'Boolean',
        'Sort' => 'Int',
        'ExtraClass' => 'Varchar(255)',
        'Style' => 'Varchar(255)',
        'Zone' => 'Varchar(50)',
    ];

    /** @var array<string, class-string> */
    private static array $has_one = [
        'Parent' => DataObject::class,
    ];

    private static array $extensions = [
        Versioned::class,
    ];

    private static array $indexes = [
        'Sort' => true,
        'ParentLookup' => [
            'type' => 'index',
            'columns' => ['ParentID', 'ParentClass', 'Zone'],
        ],
    ];

    /** @var array<string, string> */
    private static array $summary_fields = [
        'Title' => 'Title',
    ];

    private static array $searchable_fields = [
        'Title',
    ];

    /** Store used anchor names to avoid clashes */
    protected static array $used_anchors = [];

    protected ?string $anchor = null;

    abstract public function getType(): string;

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        $this->ensureSortSet();
    }

    /**
     * Set sort to end of siblings list if not already set.
     */
    public function ensureSortSet(): void
    {
        if ($this->Sort) {
            return;
        }

        if (!$this->ParentID) {
            return;
        }

        $records = Versioned::get_by_stage(GridElement::class, Versioned::DRAFT)
            ->filter([
                'ParentID' => $this->ParentID,
                'ParentClass' => $this->ParentClass,
            ]);

        $this->Sort = $records->max('Sort') + 1;
    }

    /**
     * Walk up the polymorphic Parent chain to find the owning Page.
     */
    public function getPage(): ?DataObject
    {
        $current = $this->Parent();

        // Walk up: if parent is a GridElement, keep going
        while ($current instanceof self) {
            $current = $current->Parent();
        }

        // At this point $current is either a SiteTree or null
        return $current instanceof DataObject && $current->exists() ? $current : null;
    }

    public function canView($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        $page = $this->getPage();
        if ($page !== null) {
            return $page->canView($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canEdit($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        $page = $this->getPage();
        if ($page !== null) {
            return $page->canEdit($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canDelete($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        $page = $this->getPage();
        if ($page !== null) {
            return $page->canDelete($member);
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function getAnchor(): string
    {
        if ($this->anchor !== null) {
            return $this->anchor;
        }

        $anchorTitle = $this->Title ?: 'e' . $this->ID;

        $filter = URLSegmentFilter::create();
        $titleAsURL = $filter->filter($anchorTitle);

        $result = $titleAsURL;
        $count = 1;
        while (isset(self::$used_anchors[$result]) && self::$used_anchors[$result] !== $this->ID) {
            ++$count;
            $result = $titleAsURL . '-' . $count;
        }

        self::$used_anchors[$result] = $this->ID;

        return $this->anchor = $result;
    }

    public function getTypeName(): string
    {
        return str_replace('\\', '_', static::class);
    }

    /**
     * Block schema data for the CMS editor client.
     *
     * @return array{typeName: string, actions: array{edit: string}, content: string}
     */
    public function getBlockSchema(): array
    {
        $blockSchema = $this->provideBlockSchema();
        $this->extend('updateBlockSchema', $blockSchema);

        return $blockSchema;
    }

    /**
     * @return array{typeName: string, actions: array{edit: string}, content: string}
     */
    protected function provideBlockSchema(): array
    {
        return [
            'typeName' => str_replace('\\', '_', static::class),
            'actions' => [
                'edit' => Director::absoluteURL((string) $this->CMSEditLink()),
            ],
            'content' => $this->getSummary(),
        ];
    }

    public function getSummary(): string
    {
        return '';
    }
}
```

**Note:** This is an initial version. Some methods (like `CMSEditLink`, `getStatusFlags`, `getObsoleteClassName`) depend on Versioned and SilverStripe framework APIs. These need to be verified and may need adjustments during integration testing. The implementor should check the original `BaseElement` for any additional methods called by the service layer and ensure they are available.

**Step 2: Run PHPStan on the new file**

Run: `make analyse`
Expected: Clean or minor type issues to fix

**Step 3: Commit**

```bash
git add src/Model/GridElement.php
git commit -m "feat: add GridElement abstract base class"
```

### Task 2.2: Create ContainerElement abstract base class

**Files:**
- Create: `src/Model/ContainerElement.php`

**Step 1: Write ContainerElement**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Contract\ContainerInterface;
use WeDevelop\Grid\Contract\ContainerType;

/**
 * Abstract base for structural container elements (Section, Row, Column).
 *
 * Provides the typed children relation, hierarchy config accessors,
 * and the ContainerInterface contract.
 */
abstract class ContainerElement extends GridElement implements ContainerInterface
{
    /**
     * Subclasses define their own has_many to typed children.
     * This method provides a uniform accessor.
     *
     * @return HasManyList<GridElement>
     */
    abstract public function getChildren(): HasManyList;

    #[\Override]
    public function hasChildren(): bool
    {
        return $this->getChildren()->exists();
    }

    public function getChildCountSummary(): string
    {
        $count = $this->getChildren()->count();
        $childType = $this->getChildTypeName();

        return sprintf('%d %s', $count, $count === 1 ? $childType : $childType . 's');
    }

    #[\Override]
    public function getSummary(): string
    {
        return $this->getChildCountSummary();
    }

    /**
     * Human-readable name for children (e.g., "row", "column", "element").
     * Used in summaries.
     */
    abstract protected function getChildTypeName(): string;
}
```

**Step 2: Commit**

```bash
git add src/Model/ContainerElement.php
git commit -m "feat: add ContainerElement abstract base class"
```

### Task 2.3: Create ContentElement abstract base class

**Files:**
- Create: `src/Model/ContentElement.php`

**Step 1: Write ContentElement**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Abstract base for presentational content elements (leaf nodes).
 *
 * Provides template rendering, anchor generation, search indexing.
 * Third-party/custom content blocks extend this class.
 */
abstract class ContentElement extends GridElement
{
    private static string $table_name = 'ContentElement';

    /**
     * Render this element using its template hierarchy.
     */
    public function forTemplate(): string
    {
        $templates = $this->getRenderTemplates();

        if ($templates !== []) {
            return $this->renderWith($templates);
        }

        return '';
    }

    /**
     * @return list<string>
     */
    public function getRenderTemplates(string $suffix = ''): array
    {
        $classes = ClassInfo::ancestry($this->ClassName);
        $classes[static::class] = static::class;
        $classes = array_reverse($classes);
        $templates = [];

        foreach ($classes as $class) {
            if ($class === self::class || $class === GridElement::class) {
                continue;
            }

            if ($class === DataObject::class) {
                break;
            }

            $templates[] = $class . $suffix;
        }

        $this->extend('updateRenderTemplates', $templates, $suffix);

        return $templates;
    }

    /**
     * Whether this element should be indexed in search.
     */
    public function getSearchIndexable(): bool
    {
        return (bool) $this->config()->get('search_indexable');
    }
}
```

**Step 2: Commit**

```bash
git add src/Model/ContentElement.php
git commit -m "feat: add ContentElement abstract base class"
```

### Task 2.4: Update ContainerInterface (was ElementContainerInterface)

**Files:**
- Modify: `src/Contract/ElementContainerInterface.php` → rename to `src/Contract/ContainerInterface.php`

**Step 1: Rename file and update interface**

Rename the file from `ElementContainerInterface.php` to `ContainerInterface.php`.

Update contents — remove the `getChildArea(): ElementalArea` method and replace with the new contract:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

use SilverStripe\ORM\HasManyList;
use WeDevelop\Grid\Model\GridElement;

interface ContainerInterface
{
    /** @return HasManyList<GridElement> */
    public function getChildren(): HasManyList;

    public function hasChildren(): bool;

    public function getContainerType(): ContainerType;
}
```

**Step 2: Delete old file if not already renamed**

**Step 3: Commit**

```bash
git add src/Contract/
git commit -m "refactor: rename ElementContainerInterface to ContainerInterface, remove area dependency"
```

---

## Phase 3: Migrate Container Elements

Rewrite Section, Row, Column to extend the new base classes with direct parent-child relationships.

### Task 3.1: Rewrite Section

**Files:**
- Modify: `src/Elements/ElementSection.php`

**Step 1: Rewrite to extend ContainerElement**

The class will be renamed to `Section` in Phase 7. For now, keep the filename but update the internals:

- Change `extends BaseElement` to `extends ContainerElement`
- Remove `use DNADesign\Elemental\Models\BaseElement` and `use DNADesign\Elemental\Models\ElementalArea`
- Replace `has_one ChildArea => ElementalArea` with `has_many Rows => ElementRow.Parent`
- Update `$owns`, `$cascade_deletes`, `$cascade_duplicates` to reference `Rows`
- Update `getChildren()` to return `$this->Rows()`
- Update `onAfterWrite()` scaffolding to create Row with `ParentID = $this->ID, ParentClass = static::class`
- Remove `getChildArea()` method
- Implement `getChildTypeName()` returning `'row'`

Key changes in the `has_many`:
```php
private static array $has_many = [
    'Rows' => ElementRow::class . '.Parent',
];

private static array $owns = ['Rows'];
private static array $cascade_deletes = ['Rows'];
private static array $cascade_duplicates = ['Rows'];
```

Auto-scaffolding in `onAfterWrite()`:
```php
protected function onAfterWrite(): void
{
    parent::onAfterWrite();

    if (Versioned::get_stage() !== Versioned::DRAFT) {
        return;
    }

    if ($this->Rows()->count() > 0) {
        return;
    }

    $row = ElementRow::create();
    $row->Title = static::config()->get('default_row_title');
    $row->ParentID = $this->ID;
    $row->ParentClass = static::class;
    $row->write();
}
```

**Step 2: Commit**

```bash
git add src/Elements/ElementSection.php
git commit -m "refactor: rewrite ElementSection to extend ContainerElement with direct parent-child"
```

### Task 3.2: Rewrite Row

**Files:**
- Modify: `src/Elements/ElementRow.php`

**Step 1: Same pattern as Section**

- Extend `ContainerElement`
- `has_many Columns => ElementColumn.Parent`
- `getChildren()` returns `$this->Columns()`
- Auto-scaffolding creates Column with `ParentID = $this->ID, ParentClass = static::class`
- `getChildTypeName()` returns `'column'`

**Step 2: Commit**

```bash
git add src/Elements/ElementRow.php
git commit -m "refactor: rewrite ElementRow to extend ContainerElement with direct parent-child"
```

### Task 3.3: Rewrite Column

**Files:**
- Modify: `src/Elements/ElementColumn.php`

**Step 1: Same pattern**

- Extend `ContainerElement`
- `has_many Elements => ContentElement.Parent` (or `GridElement.Parent` if columns can hold any element)
- `getChildren()` returns `$this->Elements()`
- No auto-scaffolding (Column doesn't scaffold children)
- Keep all grid settings logic (GridSettings JSON, getColumnClasses, etc.)
- `getChildTypeName()` returns `'element'`

**Step 2: Commit**

```bash
git add src/Elements/ElementColumn.php
git commit -m "refactor: rewrite ElementColumn to extend ContainerElement with direct parent-child"
```

### Task 3.4: Remove area repository

**Files:**
- Delete: `src/Repository/ElementalAreaRepositoryInterface.php`
- Delete: `src/Repository/OrmElementalAreaRepository.php`
- Delete: `tests/Integration/Repository/OrmElementalAreaRepositoryTest.php`

**Step 1: Delete the three files**

The area model no longer exists. All code that used the area repository will be updated in Phase 5 to work with direct parent lookups.

**Step 2: Commit**

```bash
git rm src/Repository/ElementalAreaRepositoryInterface.php src/Repository/OrmElementalAreaRepository.php tests/Integration/Repository/OrmElementalAreaRepositoryTest.php
git commit -m "refactor: remove ElementalArea repository (area model eliminated)"
```

---

## Phase 4: Update Repository & Service Interfaces

Update all interfaces and contracts to use `GridElement` instead of `BaseElement`/`ElementalArea`.

### Task 4.1: Update ElementRepositoryInterface → GridElementRepositoryInterface

**Files:**
- Modify: `src/Repository/ElementRepositoryInterface.php` (rename to `GridElementRepositoryInterface.php`)

**Step 1: Rename and update**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use WeDevelop\Grid\Model\GridElement;

interface GridElementRepositoryInterface
{
    /** @param positive-int $id */
    public function findById(int $id): ?GridElement;

    /**
     * Find all children of the given parent IDs, ordered by Sort ASC, ID ASC.
     *
     * @param list<positive-int> $parentIds
     * @return list<GridElement>
     */
    public function findByParentIds(array $parentIds): array;
}
```

Note: `findByAreaIds` becomes `findByParentIds` — same query logic but the semantics change from "elements in these areas" to "children of these parents."

**Step 2: Commit**

```bash
git add src/Repository/
git commit -m "refactor: rename ElementRepositoryInterface to GridElementRepositoryInterface"
```

### Task 4.2: Update OrmElementRepository → OrmGridElementRepository

**Files:**
- Modify: `src/Repository/OrmElementRepository.php` (rename to `OrmGridElementRepository.php`)

**Step 1: Update implementation**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Repository;

use SilverStripe\Core\Injector\Injectable;
use WeDevelop\Grid\Model\GridElement;

final class OrmGridElementRepository implements GridElementRepositoryInterface
{
    use Injectable;

    public function findById(int $id): ?GridElement
    {
        /** @var GridElement|null */
        return GridElement::get()->byID($id);
    }

    public function findByParentIds(array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        /** @var list<GridElement> */
        return GridElement::get()
            ->filter('ParentID', $parentIds)
            ->sort(['Sort' => 'ASC', 'ID' => 'ASC'])
            ->toArray();
    }
}
```

**Note:** The `findByParentIds` query filters by `ParentID` only (not `ParentClass`). This works because a given parent ID is unique across the system — a Section's ID won't collide with a Page's ID in practice. If this becomes a concern, add `ParentClass` filtering, but keep it simple for now.

**Step 2: Commit**

```bash
git add src/Repository/
git commit -m "refactor: rename OrmElementRepository to OrmGridElementRepository, use GridElement"
```

### Task 4.3: Update ReorderValidatorInterface and ReorderExecutorInterface

**Files:**
- Modify: `src/Contract/ReorderValidatorInterface.php`
- Modify: `src/Contract/ReorderExecutorInterface.php`

**Step 1: Replace ElementalArea with parent container**

The reorder interfaces currently take `ElementalArea $targetArea`. Since areas no longer exist, they now take the target parent (a `DataObject` — could be a ContainerElement or a Page):

```php
// ReorderValidatorInterface
interface ReorderValidatorInterface
{
    /** @return Result<GridElement> */
    public function validate(GridElement $element, DataObject $targetParent): Result;
}

// ReorderExecutorInterface
interface ReorderExecutorInterface
{
    /**
     * @param positive-int|null $afterElementId
     * @return Result<list<GridElement>>
     */
    public function execute(GridElement $element, DataObject $targetParent, ?int $afterElementId): Result;
}
```

**Step 2: Commit**

```bash
git add src/Contract/
git commit -m "refactor: update reorder interfaces to use GridElement and DataObject target parent"
```

### Task 4.4: Update HierarchyValidatorInterface

**Files:**
- Modify: `src/Validation/HierarchyValidatorInterface.php`

**Step 1: Update to use GridElement**

```php
interface HierarchyValidatorInterface
{
    /** @return Result<GridElement> */
    public function validate(GridElement $element): Result;
}
```

**Step 2: Commit**

```bash
git add src/Validation/
git commit -m "refactor: update HierarchyValidatorInterface to use GridElement"
```

---

## Phase 5: Service & Validation Refactor

Update all services, validators, and the controller to work with the new model.

### Task 5.1: Rewrite HierarchyValidationService

**Files:**
- Modify: `src/Validation/HierarchyValidationService.php`

**Step 1: Rewrite to use parent chain instead of ElementalArea**

Key changes:
- Remove `use DNADesign\Elemental\Extensions\ElementalPageExtension`
- Remove `use DNADesign\Elemental\Models\BaseElement`
- Use `GridElement` and `ContainerElement`
- Walk `Parent()` chain — if parent is a `SiteTree`, check `can_be_root`; if parent is a `ContainerElement`, check `allowed_elements`/`disallowed_elements`
- No more `ElementalPageExtension` check — instead check `$owner instanceof SiteTree`

**Step 2: Commit**

```bash
git add src/Validation/HierarchyValidationService.php
git commit -m "refactor: rewrite HierarchyValidationService for direct parent-child model"
```

### Task 5.2: Rewrite HierarchyValidationExtension

**Files:**
- Modify: `src/Validation/HierarchyValidationExtension.php`

**Step 1: Update type hint**

Change `@extends Extension<\DNADesign\Elemental\Models\BaseElement>` to `@extends Extension<\WeDevelop\Grid\Model\GridElement>`.

The rest of the logic (delegating to HierarchyValidatorInterface) stays the same.

**Step 2: Commit**

```bash
git add src/Validation/HierarchyValidationExtension.php
git commit -m "refactor: update HierarchyValidationExtension type hint to GridElement"
```

### Task 5.3: Rewrite ReorderValidator

**Files:**
- Modify: `src/Validation/ReorderValidator.php`

**Step 1: Update to work with DataObject target parent**

Same logic as HierarchyValidationService but for reorder context:
- Remove `ElementalArea` parameter, use `DataObject $targetParent`
- Same-parent check: `$element->ParentID === $targetParent->ID`
- Cross-parent: check `can_be_root` if `$targetParent instanceof SiteTree`, else check `allowed_elements`/`disallowed_elements` on the container

**Step 2: Commit**

```bash
git add src/Validation/ReorderValidator.php
git commit -m "refactor: rewrite ReorderValidator for direct parent-child model"
```

### Task 5.4: Rewrite ReorderExecutor

**Files:**
- Modify: `src/Service/ReorderExecutor.php`

**Step 1: Replace ElementalArea with DataObject target parent**

Key changes:
- `execute(GridElement $element, DataObject $targetParent, ?int $afterElementId)`
- `$targetParentId = $targetParent->ID`
- `$sourceParentId = $element->ParentID`
- Cross-parent move: `$element->ParentID = $targetParentId; $element->ParentClass = $targetParent::class;`
- Query siblings via `findByParentIds([$targetParentId])` instead of `findByAreaIds`

**Step 2: Commit**

```bash
git add src/Service/ReorderExecutor.php
git commit -m "refactor: rewrite ReorderExecutor for direct parent-child model"
```

### Task 5.5: Rewrite ReorderService

**Files:**
- Modify: `src/Service/ReorderService.php`

**Step 1: Update signature and flow**

```php
public function reorder(GridElement $element, DataObject $targetParent, ?int $afterElementId): Result
```

Remove `use DNADesign\Elemental\Models\ElementalArea` and `use DNADesign\Elemental\Models\BaseElement`.

**Step 2: Commit**

```bash
git add src/Service/ReorderService.php
git commit -m "refactor: update ReorderService for new reorder interfaces"
```

### Task 5.6: Rewrite ElementPersistenceService

**Files:**
- Modify: `src/Service/ElementPersistenceService.php`

**Step 1: Remove ReorderElements dependency**

The current `reorderElement()` method uses `DNADesign\Elemental\Services\ReorderElements`. This needs to be replaced with our own sort-insertion logic (or the method can be simplified since `ReorderExecutor` handles the actual sort computation).

Replace `BaseElement` with `GridElement` throughout.

Remove the `use DNADesign\Elemental\Services\ReorderElements` import and the `reorderElement()` method that wraps it. Instead, implement inline sort insertion:

```php
private function insertAfter(GridElement $element, int $afterElementId): void
{
    // Find the target sibling to get its Sort value
    $afterElement = GridElement::get()->byID($afterElementId);
    if ($afterElement === null) {
        $element->write();
        return;
    }

    // Bump sort values of elements after the target
    $siblings = GridElement::get()
        ->filter([
            'ParentID' => $element->ParentID,
            'ParentClass' => $element->ParentClass,
        ])
        ->where(sprintf('"Sort" > %d', (int) $afterElement->Sort));

    foreach ($siblings as $sibling) {
        $sibling->Sort = (int) $sibling->Sort + 1;
        $sibling->write();
    }

    $element->Sort = (int) $afterElement->Sort + 1;
    $element->write();
}
```

**Note:** The implementor should verify this logic against the existing tests. The original `ReorderElements` service from elemental may have additional edge cases.

**Step 2: Commit**

```bash
git add src/Service/ElementPersistenceService.php
git commit -m "refactor: remove ReorderElements dependency, implement inline sort insertion"
```

### Task 5.7: Rewrite ElementTreeBuilder → GridTreeBuilder

**Files:**
- Modify: `src/Service/ElementTreeBuilder.php` (rename to `GridTreeBuilder.php`)

**Step 1: Rewrite for direct parent-child model**

Major changes:
- Remove `getElementalRelations()` call — instead query root Sections by `ParentID = $page->ID AND ParentClass = $page::class`
- `loadAllElements()` takes root parent IDs, fetches children at each depth level
- `buildElementNode()` uses `$element->ParentID` directly (no ChildAreaID)
- `getAllowedTypes()` — the current implementation calls `$container->getElementalTypes()` which comes from `ElementalAreasExtension`. This needs to be replaced with reading `allowed_elements`/`disallowed_elements` config directly.
- Return type changes: keyed by parent ID instead of area ID (or by zone name)

The tree builder return type should change from `array<int, list<ElementNode>>` (keyed by area ID) to `array<string, list<GridNode>>` (keyed by zone name) to match the new zone-based architecture.

**Step 2: Commit**

```bash
git add src/Service/
git commit -m "refactor: rewrite ElementTreeBuilder as GridTreeBuilder for direct parent-child model"
```

### Task 5.8: Update ElementNode → GridNode

**Files:**
- Modify: `src/Model/ElementNode.php` (rename to `GridNode.php`)

**Step 1: Update the DTO**

Key changes:
- Rename class to `GridNode`
- Replace `parentAreaId` with `parentId` (the direct parent element/page ID)
- Remove `childAreaId` — containers no longer have a separate child area ID; the container's own ID is used to query children
- Add `parentClass` if needed by frontend (or omit if frontend doesn't need it)

The serialized output changes:
```php
// Before:
'parentAreaId' => $this->parentAreaId,
'childAreaId' => $this->childAreaId,

// After:
'parentId' => $this->parentId,
```

**Step 2: Commit**

```bash
git add src/Model/
git commit -m "refactor: rename ElementNode to GridNode, replace areaId with parentId"
```

### Task 5.9: Rewrite GridController (was ElementalGridController)

**Files:**
- Modify: `src/Controllers/ElementalGridController.php` (rename to `GridController.php`)

**Step 1: Major controller rewrite**

Key changes:
- Rename class to `GridController`
- Update `$url_segment` from `'elemental-grid'` to `'grid'`
- Remove `areaRepository` dependency
- Update `apiReadTree()`: no more `getElementalRelations()` check — just pass page to tree builder
- Update `apiCreate()`: `elementalAreaID` in body → `parentId` + `parentClass`. Look up parent directly. Set `$newElement->ParentID` and `$newElement->ParentClass`.
- Update `apiReorder()`: `targetAreaID` → `targetParentId`. Look up target parent (could be ContainerElement or Page).
- Update `apiDuplicate()`: no area lookup needed, use element's `ParentID/ParentClass` directly
- Update PHPStan type annotations for request/response shapes

**Step 2: Commit**

```bash
git add src/Controllers/
git commit -m "refactor: rewrite ElementalGridController as GridController"
```

---

## Phase 6: Frontend Contract Update

Update TypeScript types and API client to match the new backend contract.

### Task 6.1: Update Zod schemas in elements.ts

**Files:**
- Modify: `client/src/types/elements.ts`

**Step 1: Update schemas**

Key changes:
- `parentAreaId` → `parentId` in `baseFieldsSchema`
- Remove `childAreaId` from container schemas
- The tree response key changes from area ID to zone name (still `z.record(z.string(), ...)` so the schema itself may not need structural changes)

**Step 2: Run typecheck**

Run: `npm run typecheck`
Expected: Errors in components that reference `parentAreaId` or `childAreaId`

**Step 3: Commit**

```bash
git add client/src/types/
git commit -m "refactor: update Zod schemas for new parent-child model"
```

### Task 6.2: Update API endpoints

**Files:**
- Modify: `client/src/api/endpoints.ts`

**Step 1: Update request types**

- `CreateElementParams`: `elementalAreaID` → `parentId` (+ `parentClass`)
- `ReorderElementParams`: `targetAreaID` → `targetParentId`

**Step 2: Commit**

```bash
git add client/src/api/
git commit -m "refactor: update API endpoint types for new parent-child model"
```

### Task 6.3: Update React components and hooks

**Files:**
- Modify: Any component/hook that references `parentAreaId`, `childAreaId`, or `elementalAreaID`

**Step 1: Find and update all references**

Search for `parentAreaId`, `childAreaId`, `elementalAreaID`, `targetAreaID` across `client/src/` and update to new field names.

**Step 2: Run typecheck and tests**

Run: `npm run typecheck && npm run test`
Expected: All pass

**Step 3: Commit**

```bash
git add client/src/
git commit -m "refactor: update React components for new parent-child API contract"
```

---

## Phase 7: YAML Config, Templates, Class Renames & Final Cleanup

### Task 7.1: Rename element classes

**Files:**
- Rename: `src/Elements/ElementSection.php` → `src/Elements/Section.php` (class rename inside too)
- Rename: `src/Elements/ElementRow.php` → `src/Elements/Row.php`
- Rename: `src/Elements/ElementColumn.php` → `src/Elements/Column.php`

**Step 1: Rename files and update class names**

In each file:
- Rename the class (e.g., `class ElementSection` → `class Section`)
- Update `$table_name` (e.g., `'ElementSection'` → `'Section'`)
- Update cross-references (Section references Row, Row references Column)

**Step 2: Update all references across the codebase**

Search for `ElementSection`, `ElementRow`, `ElementColumn` in all PHP, YAML, and fixture files. Update to `Section`, `Row`, `Column`.

**Step 3: Commit**

```bash
git add src/Elements/ tests/ _config/
git commit -m "refactor: rename ElementSection/Row/Column to Section/Row/Column"
```

### Task 7.2: Rename remaining service/controller files

**Files:**
- Verify all renames from the naming table in the design doc are complete:
  - `ElementTreeBuilder.php` → `GridTreeBuilder.php`
  - `ElementalGridController.php` → `GridController.php`
  - `ElementNode.php` → `GridNode.php`
  - `ElementPersistenceService.php` → `PersistenceService.php`
  - `OrmElementRepository.php` → `OrmGridElementRepository.php`
  - `ElementRepositoryInterface.php` → `GridElementRepositoryInterface.php`
  - `ElementContainerInterface.php` → `ContainerInterface.php`
  - `ElementalGridLeftAndMainExtension.php` → `GridLeftAndMainExtension.php`

**Step 1: Rename any files not yet renamed in previous phases**

**Step 2: Commit**

```bash
git add src/
git commit -m "refactor: complete file renames per design doc naming table"
```

### Task 7.3: Rewrite YAML config files

**Files:**
- Modify: `_config/elements.yml`
- Modify: `_config/cms.yml`
- Modify: `_config/dev.yml`

**Step 1: Rewrite elements.yml**

- Remove `ElementalAreasExtension` from all container classes (no longer needed)
- Remove `DNADesign\Elemental\Forms\ElementalAreaField` Injector override
- Update all class paths to use new names
- Apply `HierarchyValidationExtension` to `WeDevelop\Grid\Model\GridElement` instead of `DNADesign\Elemental\Models\BaseElement`
- Update Injector bindings for renamed interfaces/classes

**Step 2: Rewrite cms.yml**

- Remove the `DNADesign\Elemental\Forms\ElementalAreaField` Injector replacement (no longer needed)
- Update extension class name: `GridLeftAndMainExtension`
- Update resource paths: `wedevelopnl/silverstripe-grid:client/dist/...`
- Remove elemental bundle blocking from `GridLeftAndMainExtension` (elemental is conflicted, bundles won't exist)

**Step 3: Rewrite dev.yml**

- Update fixture controller path
- Update fixture file paths: `wedevelopnl/silverstripe-grid:tests/E2E/Fixture/...`
- Update class references in post_actions from `DNADesign\Elemental\Models\BaseElement` to `WeDevelop\Grid\Model\GridElement` (or `ContentElement`)
- Update `ElementRow`, `ElementSection` → `Row`, `Section`
- Update controller route from `elemental-grid-fixtures` to `grid-fixtures`

**Step 4: Commit**

```bash
git add _config/
git commit -m "refactor: rewrite YAML config for new class hierarchy and package identity"
```

### Task 7.4: Move and rename templates

**Files:**
- Move: `templates/WeDevelop/ElementalGrid/` → `templates/WeDevelop/Grid/`
- Rename element templates: `ElementSection.ss` → `Section.ss`, etc.
- Update or remove `templates/DNADesign/` directory (holder templates)

**Step 1: Move template directory**

```bash
mkdir -p templates/WeDevelop/Grid/Elements
mkdir -p templates/WeDevelop/Grid/Forms
mv templates/WeDevelop/ElementalGrid/Elements/ElementSection.ss templates/WeDevelop/Grid/Elements/Section.ss
mv templates/WeDevelop/ElementalGrid/Elements/ElementRow.ss templates/WeDevelop/Grid/Elements/Row.ss
mv templates/WeDevelop/ElementalGrid/Elements/ElementColumn.ss templates/WeDevelop/Grid/Elements/Column.ss
mv templates/WeDevelop/ElementalGrid/Forms/GridEditorField_holder.ss templates/WeDevelop/Grid/Forms/GridEditorField_holder.ss
```

**Step 2: Update or remove DNADesign holder templates**

The `templates/DNADesign/Elemental/Layout/` directory contains holder templates (`ElementSectionHolder.ss`, etc.). These need to be moved to our namespace or the `controller_template` config updated to point to a new location.

**Step 3: Remove old template directories**

```bash
rm -rf templates/WeDevelop/ElementalGrid/
rm -rf templates/DNADesign/
```

**Step 4: Commit**

```bash
git add templates/
git commit -m "refactor: move and rename templates to new namespace"
```

### Task 7.5: Update GridEditorField

**Files:**
- Modify: `src/Forms/GridEditorField.php`

**Step 1: Remove ElementalArea dependency**

The field currently receives an `ElementalArea` in its constructor. Change to receive a page ID and zone name:

```php
public function __construct(string $name, int $pageId, string $zone = 'Main', array $blockTypes = [])
{
    $this->pageId = $pageId;
    $this->zone = $zone;
    $this->blockTypes = $blockTypes;

    parent::__construct($name);
    $this->addExtraClass('grid-editor__container no-change-track');
}
```

Update `getSchemaDataDefaults()`:
```php
$schemaData['grid-page-id'] = $this->pageId;
$schemaData['grid-zone'] = $this->zone;
```

**Step 2: Commit**

```bash
git add src/Forms/GridEditorField.php
git commit -m "refactor: update GridEditorField to use pageId and zone instead of ElementalArea"
```

### Task 7.6: Rewrite or remove GridLeftAndMainExtension

**Files:**
- Modify: `src/Extensions/ElementalGridLeftAndMainExtension.php` (rename to `GridLeftAndMainExtension.php`)

**Step 1: Simplify**

Since elemental is now conflicted (not installed), the `Requirements::block()` calls for elemental bundles are unnecessary. Either:
- Remove the extension entirely if it does nothing else
- Or keep it as a no-op placeholder for future use

**Step 2: Commit**

```bash
git add src/Extensions/
git commit -m "refactor: simplify GridLeftAndMainExtension (elemental bundles no longer present)"
```

### Task 7.7: Create page extension

**Files:**
- Create: `src/Extensions/GridPageExtension.php`

**Step 1: Write the page extension**

This replaces `ElementalAreasExtension` and `ElementalPageExtension`. Applied to `SiteTree` via YAML:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use WeDevelop\Grid\Elements\Section;
use WeDevelop\Grid\Forms\GridEditorField;

/**
 * Adds grid editing capability to SiteTree pages.
 *
 * @extends Extension<SiteTree>
 */
class GridPageExtension extends Extension
{
    private static array $has_many = [
        'Sections' => Section::class . '.Parent',
    ];

    private static array $owns = [
        'Sections',
    ];

    private static array $cascade_deletes = [
        'Sections',
    ];

    private static array $cascade_duplicates = [
        'Sections',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldToTab(
            'Root.Main',
            GridEditorField::create('GridEditor', (int) $this->owner->ID),
        );
    }
}
```

**Step 2: Register in YAML**

Add to `_config/elements.yml` (or a new `_config/page.yml`):

```yaml
SilverStripe\CMS\Model\SiteTree:
  extensions:
    Grid: WeDevelop\Grid\Extensions\GridPageExtension
```

**Step 3: Commit**

```bash
git add src/Extensions/GridPageExtension.php _config/
git commit -m "feat: add GridPageExtension to replace ElementalAreasExtension/ElementalPageExtension"
```

### Task 7.8: Update test files

**Files:**
- Modify: ALL test files in `tests/`

**Step 1: Bulk update**

- Update all remaining class references (`ElementSection` → `Section`, `BaseElement` → `GridElement`, etc.)
- Update test fixture YAML files with new class names
- Rename test files (`ElementSectionTest.php` → `SectionTest.php`, etc.)
- Update test class names to match
- Remove/rewrite tests for deleted classes (area repository)

**Step 2: Run full test suite**

Run: `make test && npm run test`
Expected: All pass (some tests may need structural updates for the new parent-child model)

**Step 3: Commit**

```bash
git add tests/
git commit -m "refactor: update all tests for new class hierarchy and naming"
```

### Task 7.9: Update E2E fixture YAML files

**Files:**
- Modify: All files in `tests/E2E/Fixture/`

**Step 1: Update class references**

Replace all `WeDevelop\Grid\Elements\ElementSection` → `WeDevelop\Grid\Elements\Section` (and Row, Column).

Replace `DNADesign\Elemental\Models\ElementalArea` references — these no longer exist. The fixture YAML format changes: instead of creating `ElementalArea` records and linking elements to them, elements now link directly to their parent container or page.

**Important:** The fixture YAML ordering convention (bottom-up) still applies, but the linking mechanism changes. Instead of `ParentID` pointing to an `ElementalArea`, it points to the parent container's ID. The `ParentClass` field also needs to be set.

**Step 2: Update E2E fixture helper if endpoint changed**

If the fixture controller route changed from `/dev/elemental-grid-fixtures/` to `/dev/grid-fixtures/`, update `tests/E2E/helpers/fixtures.ts`.

**Step 3: Run E2E tests**

Run: `make test-e2e`
Expected: All pass

**Step 4: Commit**

```bash
git add tests/E2E/
git commit -m "refactor: update E2E fixtures for new class hierarchy and direct parent-child"
```

### Task 7.10: Final verification

**Step 1: Run full QA suite**

```bash
make qa          # PHPStan + PHP tests + JS QA
npm run qa       # lint + typecheck + test
make test-e2e    # E2E tests (requires Docker)
```

**Step 2: Search for any remaining references**

Search the entire codebase for:
- `DNADesign\Elemental` — should be zero results
- `ElementalGrid` — should be zero results (except possibly in git history or beads)
- `ElementalArea` — should be zero results
- `BaseElement` — should be zero results
- `ElementSection` / `ElementRow` / `ElementColumn` — should be zero results

**Step 3: Final commit**

```bash
git add -A
git commit -m "chore: final cleanup after elemental dependency removal"
```

---

## Dependency Graph

```
Phase 1 (namespace rename)
  ↓
Phase 2 (base classes)
  ↓
Phase 3 (container elements + delete area repo)
  ↓
Phase 4 (interfaces)
  ↓
Phase 5 (services + controller)
  ↓
Phase 6 (frontend)
  ↓
Phase 7 (YAML, templates, final renames, tests, cleanup)
```

Each phase depends on all prior phases. Within a phase, tasks can sometimes be parallelized (noted where applicable).

## Risk Notes

- **Integration tests will break early** — Phases 2-3 change the inheritance hierarchy, which breaks all integration tests that create container elements. These tests won't pass again until Phase 5 (services) and Phase 7 (test updates) are complete. Unit tests should remain stable earlier.
- **E2E tests require Docker** — E2E fixture YAML changes need a running Docker environment to verify. Save E2E verification for the very end.
- **PHPStan may not pass between phases** — This is expected. Run it as a diagnostic tool, not a gate, until Phase 7.
- **The `findByParentIds` query** — Currently filters by `ParentID` only. If SilverStripe assigns the same auto-increment ID to both a `GridElement` and a `SiteTree` record (which it doesn't — they have separate tables and ID sequences), this would be a problem. It's safe in practice.
