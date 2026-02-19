# Grid Editor Frontend Design — "Layered Blocks"

**Date:** 2026-02-19
**Status:** Approved
**Scope:** First iteration — read-only visual grid rendering for demo

## Context

The SilverStripe Elemental Grid module converts `dnadesign/silverstripe-elemental` into a grid-based content block system. The backend data model (Section > Row > Column > Element), API layer (REST endpoints + TanStack Query), and type system (Zod schemas) are in place. The current frontend is a proof-of-life `<ul>` tree.

This design defines the first visual frontend iteration: rendering the element tree as an actual grid layout inside the SilverStripe admin panel.

## Audience

Primary: stakeholders and sales demos. The design must also feel intuitive to content editors — the eventual end users.

## Design Direction: "Layered Blocks"

A component-oriented card layout where each hierarchy level (section, row, column, element) has distinct visual treatment. The grid structure is immediately visible through nesting, proportional column widths, and fraction badges.

Eliminated alternatives:
- **"Blueprint" (Grid Inspector):** Too technical — column guides and CSS class labels are developer-oriented, not demo-friendly.
- **"Canvas" (WYSIWYG Preview):** Duplicates SilverStripe's existing live preview / split mode. Doesn't communicate the grid structure clearly.

## Scope

**In scope (first iteration):**
- Read-only visual rendering of the Section > Row > Column > Element tree
- Viewport switcher driven by the active grid adapter
- Real Bootstrap grid classes for column layout (adapter-driven)
- Publication state indicators at all 4 hierarchy levels
- Empty states for columns, rows, and the editor

**Out of scope (future iterations):**
- Column width controls (sliders, drag-to-resize)
- Drag-and-drop reordering (dnd-kit is installed but unused)
- Add/remove elements UI
- Inline editing / expand-collapse element cards
- Actions menu (publish, unpublish, duplicate, archive)

## Overall Layout

The grid editor replaces the current `<ul>` tree inside the SilverStripe admin content area. It sits in the `ElementalArea` position below the page fields.

```
Page Content Tab
├── Page name, URL segment, Navigation label (existing)
├── ElementalArea
│   └── Grid Editor
│       ├── Viewport Switcher (segmented control)
│       ├── Section 1
│       │   └── Rows > Columns > Elements
│       ├── Section 2
│       │   └── ...
│       └── (empty state if no sections)
└── Metadata (existing)
```

## Viewport Switcher

A segmented control pinned to the top of the editor area.

- Tabs populated dynamically from the active adapter's `getViewports()` (label + key)
- Initially selected tab determined by `getDefaultViewport()` (Bootstrap: `md`)
- Active tab: SilverStripe primary blue background, white text
- Inactive tabs: neutral gray
- Switching tabs re-renders all column widths/offsets/visibility using the selected viewport's `GridSettings` values

## Visual Hierarchy

### Level 1 — Section (outermost shell)

- Double-line border (`2px solid`)
- Background: `#f0f4f8` (cool light gray-blue)
- Title: bold text, small caps style, top-left
- Full width of the editor area
- Inner padding: `16px`

### Level 2 — Row (horizontal container)

- Single-line border (`1px solid`), rounded corners (`4px`)
- Background: `#ffffff` (white)
- Title: small muted label, top-left
- Uses the adapter's row classes directly: `class="{adapter.getRowClasses()}"` (Bootstrap: `class="row"`)
- Gap between columns handled by the framework's gutter system

### Level 3 — Column (proportional width cell)

- No outer border — defined by its grid placement within the row
- Background: `#f8f9fa` (barely-there gray)
- Fraction badge in header: `4/12` shown as a small pill/tag
- Uses adapter-generated **base classes** (no viewport prefix): `col-4`, `offset-2`
- Elements stack vertically inside with `8px` gap

**Viewport class strategy:** The viewport switcher controls which `GridSettings` values are read. We render using unprefixed base classes (`col-{N}`, `offset-{N}`) so they always apply regardless of the editor's actual width. When the user switches viewport, we read that viewport's width/offset from `GridSettings` and re-render with the corresponding base classes.

**Hidden columns:** When `visible: false` for the selected viewport:
- Column is still rendered (content editors need to know it exists)
- `opacity: 0.4`
- Diagonal stripe overlay pattern (CSS-only)
- Fraction badge replaced with "hidden"

### Level 4 — Element (content block card)

- White card with subtle shadow (`0 1px 3px rgba(0,0,0,0.08)`)
- Rounded corners (`4px`)
- Two lines maximum:
  - Line 1: Type icon (`font-icon-*` system) + element title (semi-bold)
  - Line 2: Content preview from `blockSchema.content`, truncated with ellipsis. Fallback: "No preview available" (muted)
- Cards are read-only in this iteration — no expand/collapse, no actions

## Publication State

All 4 levels use a **3px left border accent**:
- Draft: `#0071c4` (SilverStripe blue)
- Published: `#3fa142` (green)
- Modified: `#d4a017` (amber)

State is derived from `isPublished` and `isLiveVersion` fields (existing `deriveElementStatus()` utility).

## Empty States

1. **Empty column** (no elements): Dashed-border placeholder with muted text "No content blocks"
2. **Empty row** (no columns): Placeholder "No columns" (defensive — auto-scaffolding prevents this normally)
3. **Empty editor** (no sections): Centered message "No sections yet"

## Technical Constraints

- The editor lives inside the SilverStripe admin panel (Bootstrap 4, font-icon system, Injector pattern)
- React 18 + TypeScript 5.9, built with Vite in IIFE lib mode
- React and ReactDOM are external globals from the SS admin bundle
- The component mounts via jQuery Entwine bridge (`grid-editor__container`)
- Data fetched via existing `useElementTree(pageId)` hook (TanStack Query + Zod validation)
- The active grid adapter determines viewports and CSS classes — hardwired to Bootstrap for this demo

## Known API Gaps

These are **not blockers** for the read-only first iteration but will be needed for future editing:

1. `GridSettings` is not included in the `readTree` API response — `ElementNode` DTO needs a `gridSettings` field for columns
2. No `updateGridSettings` endpoint — saving column width changes requires a new API endpoint
3. No `reorder/move` endpoint — drag-and-drop requires a new API endpoint
4. No adapter registration in DI config — the frontend has no way to discover the active adapter's viewports; for the demo this can be hardcoded or passed via a new API endpoint

**Blocker for this iteration:** Gap #1 — the `readTree` response must include `gridSettings` on column nodes so the frontend can render column widths per viewport.
