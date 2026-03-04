---
name: e2e-test-scaffold
description: Scaffolds a Playwright E2E test spec structured as a multi-step user journey. Starts by asking what user story is being tested, then generates the spec with fixture loading, authenticated navigation, and sequential interactions that mirror a real CMS editing session. Use when creating new E2E tests.
---

This skill generates Playwright E2E specs that test **complete user flows**, not individual operations. It enforces the project's E2E philosophy: E2E tests prove the assembled system delivers the user story.

## Step 1: Identify the User Story

**Start by asking**: "What user story or flow are you testing?"

Do NOT accept answers like:
- "Test the add element button" → this is a single operation, not a user story
- "Test that delete works" → this is a functional test

Guide toward answers like:
- "Content editor builds a new page section, adds rows and columns, reorders elements, then previews the result"
- "Content editor modifies grid column widths across viewport breakpoints and verifies the preview reflects the changes"
- "Content editor manages draft and published states of sections with nested content"

If the user provides a single operation, push back:
> "That's a single operation that would be better tested at the unit or integration level. E2E tests should cover complete user journeys. What's the full workflow this operation is part of? For example, if you want to test 'add element', the full journey might be: editor opens a page, adds a section, configures columns, adds content elements, and publishes."

## Step 2: Determine Fixture Needs

Ask:
1. **Can an existing fixture work?** Available fixtures: `element-tree`, `empty-page`, `collapse-test`, `complex-page`
2. **Need a new fixture?** If so, what initial page state is required?
3. **Post-actions needed?** Does the test need published content, unpublished drafts, or modified records?

## Step 3: Generate the Spec

Create the spec at `tests/E2E/specs/{feature-area}.spec.ts`:

```typescript
import { expect, test } from '@playwright/test';
import { loadFixture, resetFixtures } from '../helpers/fixtures';

test.describe('{Feature Area} — {User Story Summary}', () => {
  test.afterAll(async ({ request }) => {
    await resetFixtures(request);
  });

  test('{describes the full user journey}', async ({ page }) => {
    // === Setup: Load fixture and navigate ===
    const fixture = await loadFixture(page.request, '{fixture-name}');
    await page.goto(`/admin/pages/edit/show/${fixture.pageId}`);
    await expect(page.getByTestId('grid-editor-loading')).toBeHidden({ timeout: 15_000 });

    // === Step 1: {First meaningful user action} ===
    // ... interactions and assertions

    // === Step 2: {Second meaningful user action} ===
    // ... interactions and assertions

    // === Step 3: {Third meaningful user action} ===
    // ... interactions and assertions

    // === Verification: {Final state check} ===
    // ... final assertions that prove the journey succeeded
  });
});
```

### Key Conventions

- **Use `page.request`** (not standalone `request`) for fixture loading when a `page` object exists — shares browser cookies
- **Wait for grid load**: Always `await expect(page.getByTestId('grid-editor-loading')).toBeHidden({ timeout: 15_000 })` before interacting
- **Navigate to CMS editor**: `page.goto(\`/admin/pages/edit/show/${fixture.pageId}\`)`
- **Access secondary IDs**: `fixture.fixtureMap['ClassName']['identifier']`
- **Frontend URL**: `fixture.pageUrl`
- **Step comments**: Use `// === Step N: description ===` to mark journey phases
- **Assertions within steps**: Assert intermediate state between steps, not just the final result
- **Serial execution**: Tests run with `workers: 1` — they share database state

### Available Test Selectors

- `getByTestId('grid-editor-loading')` — loading spinner
- `getByTestId('section-block')` — section containers
- `getByTestId('collapse-toggle')` — collapse toggle buttons
- `getByTestId('column-block')` — column containers
- `getByTestId('viewport-button')` — viewport switcher buttons
- `getByTestId('column-badge')` — column width badge
- `.row-block` (CSS class) — row containers
- `getByRole('group', { name: 'Viewport size' })` — viewport switcher group
- `getByRole('button', { name: 'Medium', exact: true })` — viewport buttons by name

## Step 4: Generate Fixture YAML (if needed)

If a new fixture is required, create it at `tests/E2E/Fixture/{Name}.yml`:

### YAML Ordering Rule

Elements **must** be listed bottom-up to prevent auto-scaffolding duplicates:
1. Leaf content elements first
2. Then `ElementColumn` (with `GridSettings` JSON)
3. Then `ElementRow`
4. Then `ElementSection`
5. Then `Page` last

### Register the Fixture

Add to `_config/dev.yml`:

```yaml
WeDevelop\ElementalGrid\Dev\FixtureLoader:
  fixtures:
    new-fixture-name: 'wedevelopnl/silverstripe-elemental-grid:tests/E2E/Fixture/NewFixture.yml'
```

Or with post-actions:

```yaml
    new-fixture-name:
      path: 'wedevelopnl/silverstripe-elemental-grid:tests/E2E/Fixture/NewFixture.yml'
      post_actions:
        - action: publish_recursive
          class: Page
          identifier: e2e_page
```

### Available Post-Actions

- `publish_recursive` — publishes the record and all owned relations
- `unpublish` — removes from LIVE stage (creates draft-only state)
- `modify` — sets specific fields and writes (creates draft-modified state)

## Quality Check

Before finalizing, verify:
- [ ] Spec tests a complete user journey (3+ distinct user actions minimum)
- [ ] Spec is NOT a single-operation functional test
- [ ] Fixture YAML uses bottom-up ordering
- [ ] All page URLSegments start with `e2e-`
- [ ] Uses `page.request` for fixture loading (not standalone `request`)
- [ ] Waits for grid editor loading before interacting
- [ ] Has `resetFixtures` in `afterAll`
- [ ] Test name describes the user journey, not a single operation
