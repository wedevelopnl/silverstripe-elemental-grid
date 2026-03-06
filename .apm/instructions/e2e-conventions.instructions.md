---
description: E2E test philosophy, Playwright patterns, and custom fixture loading system
applyTo: "**/*"
---

# E2E Test Conventions

## Test Philosophy

E2E tests validate **complete user flows**, not individual operations. Each spec describes a realistic user journey that exercises multiple units working together in a real browser+Docker environment.

- **E2E tests answer**: "Does this user story actually work end-to-end?"
- **E2E tests do NOT answer**: "Does this button click produce this API call?" — that's a functional test disguised as E2E, carrying all the cost (browser, Docker, fixtures) with none of the integration coverage benefit.

**Coverage boundaries**: Unit tests cover individual operations. Integration tests cover service coordination. E2E tests prove the assembled system delivers the user story.

### Anti-Pattern: One-Operation-Per-Spec

Do NOT write specs like:
- "should add element" → assert element appears
- "should delete row" → assert element gone
- "should reorder" → assert new order

These are expensive functional tests. Instead, write multi-step user journeys:
- "Content editor builds a page section with rows and columns, reorders elements, and publishes" — one spec covering the full authoring flow.

Aim for **3-5 meaningful user journey specs** per feature area, not 15-20 narrow operation tests.

## Fixture System

The project uses a custom HTTP-based fixture system, not Playwright's built-in fixtures.

### Architecture

- `FixtureController` — HTTP endpoints at `/dev/grid-fixtures/{load,reset}`, gated to dev environment only
- `FixtureLoader` — Loads YAML fixture files via SilverStripe's `FixtureFactory`, applies post-actions
- `FixturePostAction` — Post-write operations: `publish_recursive`, `unpublish`, `modify` (field updates)
- `FixtureResult` — JSON response with `pageId`, `pageUrl`, `fixtureMap`

### Loading Fixtures in Specs

```typescript
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('Feature area', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('user journey description', async ({ page }) => {
    const fixture = await loadFixture(page.request, 'fixture-name');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(page.getByTestId('grid-editor-loading')).toBeHidden({ timeout: 15_000 });
    // ... multi-step user journey assertions
  });
});
```

**Important**: Use `page.request` (not the standalone `request` fixture) when a `page` object is available — this shares browser cookies and avoids `strict_user_agent_check` session invalidation.

### Fixture YAML Conventions

- All pages must use `e2e-` as the URLSegment prefix (this is how `reset()` identifies E2E data)
- Order elements **bottom-up**: leaf elements before columns, columns before rows, rows before sections, sections before the page. This prevents `onAfterWrite` auto-scaffolding from creating duplicate children
- `GridSettings` is stored as a JSON string on `Column`
- Register fixtures in `_config/dev.yml` under `FixtureLoader.fixtures`

### Post-Actions

Post-actions run after YAML write, still in DRAFT stage:
- `publish_recursive` — calls `publishRecursive()` on the record
- `unpublish` — calls `doUnpublish()` on the record
- `modify` — sets specific fields and writes (creates draft-modified state)

### Available Fixtures

Registered in `_config/dev.yml`: `element-tree`, `empty-page`, `collapse-test`, `drag-and-drop`, `multi-zone`, `complex-page`

## Locator Strategy

E2E specs test **what is rendered**, not implementation details. Locators must be resilient to theme and markup changes.

### Preferred: `getByTestId`

Use `data-testid` attributes as the primary locator strategy. These are stable, intentional contracts between the component and the test:

```typescript
page.getByTestId('section-block')
page.getByTestId('column-badge')
page.getByTestId('viewport-button')
```

### Acceptable: Accessible Roles and Labels

Use `getByRole`, `getByLabel`, `getByText` when testing from the user's perspective:

```typescript
page.getByRole('button', { name: 'Medium', exact: true })
page.getByRole('group', { name: 'Viewport size' })
```

### Anti-Pattern: CSS Class and ID Selectors

**Do NOT use CSS class selectors** (`.row-block`, `.col-md-6`) or DOM IDs (`#Form_EditForm_Title`) unless absolutely necessary. These tie the test to the theme/CSS layer, making specs extremely brittle — a CSS refactor or theme change breaks every test that uses class selectors.

```typescript
// Wrong — brittle, tied to CSS implementation
page.locator('.row-block')
page.locator('#Form_EditForm_Title')

// Correct — stable, tests what is rendered
page.getByTestId('row-block')
page.getByRole('textbox', { name: 'Title' })
```

If a `data-testid` doesn't exist for an element you need to locate, **add one to the component** rather than reaching for a class selector.

## Playwright Patterns

- **Serial execution**: `fullyParallel: false`, `workers: 1` — tests share database state
- **Auth**: Global setup authenticates as `admin`/`admin`, stores state in `tests/E2E/.auth/admin.json`
- **Base URL**: Resolved from `E2E_BASE_URL` env var or `WEB_PORT` in `.docker/.env`
- **Wait for grid**: Always wait for `getByTestId('grid-editor-loading')` to be hidden (15s timeout) before asserting grid content
- **Navigate to editor**: `page.goto(\`/admin/pages/edit/show/${fixture.pageId}\`)`
- **Fixture map**: Access secondary page/element IDs via `fixture.fixtureMap['ClassName']['identifier']`
