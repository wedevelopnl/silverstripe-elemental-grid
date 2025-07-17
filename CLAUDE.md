# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the `wedevelopnl/silverstripe-elemental-grid` module, which extends SilverStripe's Elemental module to provide a Bootstrap-style grid system for content blocks. The module allows content editors to arrange elemental blocks in rows and columns with responsive column sizing.

## Requirements

- PHP ^8.0
- SilverStripe Framework ^5.0
- SilverStripe Admin ^2.4  
- DNADesign Elemental ^5.0
- Node.js ^18.x for frontend development

## Common Development Commands

### Frontend Development
```bash
# Install dependencies
yarn

# Build production assets
yarn build

# Build development assets
yarn dev

# Watch for changes during development
yarn watch

# Run linting
yarn lint

# Run only JavaScript linting
yarn lint-js

# Fix JavaScript linting issues
yarn lint-js-fix

# Run only SCSS linting  
yarn lint-sass

# Run tests
yarn test

# Run tests with coverage
yarn coverage
```

### Docker Development (Recommended)
```bash
# Build and start containers
make build

# Start containers
make up

# Stop containers
make down

# Open shell in container
make sh

# Watch frontend assets (in container)
make yarn-watch

# Build production assets (in container)
make yarn-build

# Run PHP code style tests
make test

# Fix PHP code style issues
make fix-cs
```

### PHP Development
```bash
# Fix code styling
./vendor/bin/php-cs-fixer fix

# Run code style tests
./vendor/bin/php-cs-fixer fix --diff --dry-run
```

## SilverStripe 5.3+ Compatibility Issues and Solutions

### Critical Compatibility Problem

This module worked fine on SilverStripe < 5.3 but caused **infinite JavaScript loops** when upgraded to SilverStripe 5.3+. The infinite loops occurred when opening the page admin interface.

### Root Cause Analysis

The issue was caused by incompatible React component registration patterns in the SilverStripe 5.3+ Injector system:

1. **Force Registration Issue**: Using `Injector.component.registerMany()` with `{ force: true }` caused infinite re-registration loops
2. **Inject Pattern Incompatibility**: Complex `inject()` and `compose()` patterns used throughout grid components became incompatible with the new Injector system
3. **Component Dependency Issues**: Grid components like `ElementList`, `Element`, and `ElementHeader` using deep inject patterns caused "Cannot set properties of undefined" errors

### Solution Strategy

The fix involved simplifying the component registration to avoid problematic patterns:

1. **Remove Force Option**: Eliminated `{ force: true }` from all registrations
2. **Register Safe Components Only**: Only register components that don't use complex inject patterns
3. **Proper Registration Order**: Ensure dependencies are registered before consumers
4. **Avoid Problematic Components**: Skip registration of components with deep inject/compose patterns

### Final Working Implementation

**File**: `client/src/bundles/bundle.js`

```javascript
import React from 'react';
import Injector from 'lib/Injector';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

window.document.addEventListener('DOMContentLoaded', () => {
  // Register buttons directly so toolbar inject() can find them
  Injector.component.register('AddBlockToBottomButton', AddBlockToBottomButton);
  Injector.component.register('AddBlockToTopButton', AddBlockToTopButton);
  Injector.component.register('ColumnSize', ColumnSize);
  
  // Register the toolbar that uses the buttons
  Injector.transform('elemental-grid-toolbar', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
  });
});
```

## Key Learnings for SilverStripe 5.3+ Development

### What Works in SilverStripe 5.3+

✅ **Simple component registration**: `Injector.component.register('ComponentName', Component)`  
✅ **Transform-based overrides**: `Injector.transform('name', updater => { updater.component(...) })`  
✅ **Direct component dependencies**: Register dependencies before consumers  
✅ **Clean inject patterns**: Simple inject with minimal dependencies  

### What Causes Issues in SilverStripe 5.3+

❌ **Force registration**: `registerMany({}, { force: true })`  
❌ **Complex inject patterns**: `inject(['A', 'B', 'C'], (a, b, c) => ({ a, b, c }))`  
❌ **Deep component composition**: Multiple levels of inject/compose  
❌ **Overriding core components**: Replacing base elemental components  

### Component Registration Best Practices

1. **Register dependencies first**: Buttons before toolbar that uses them
2. **Use simple names**: Avoid complex namespacing in registration
3. **Minimal inject patterns**: Keep inject dependencies to essential components only
4. **Test incrementally**: Add one component at a time to isolate issues
5. **Avoid core overrides**: Work with existing components rather than replacing them

## Grid Button Functionality

### Button Implementation

The grid module provides two buttons for adding elements:

- **Add to Bottom**: Adds elements at the end of the element list
- **Add to Top**: Adds elements at the beginning of the element list

### Critical Fix for "Add to Bottom" Behavior

**Problem**: The "Add to bottom" button was adding elements to the top instead of the bottom.

**Root Cause**: Both buttons were passing `insertAfterElement={0}`, which means "insert after element ID 0" (beginning of list).

**Solution**: Changed `AddBlockToBottomButton.js` to pass `insertAfterElement={null}` instead of `0`:

```javascript
<AddElementPopoverComponent
  // ... other props
  insertAfterElement={null}  // Changed from {0}
  insertAtBottom
/>
```

This allows the backend `ElementalResolver.php` logic to correctly handle the `insertAtBottom` flag by finding the last element and inserting after it.

## CSS Styling

### Button Display Fix

**File**: `client/src/styles/bundle.scss`

Added CSS to make grid buttons display inline horizontally instead of stacked vertically:

```scss
/* ELEMENT TOOLBAR STYLING */
.element-editor__toolbar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  
  .btn {
    flex: 1;
    min-width: auto;
  }
}
```

## Development Workflow

### Testing SilverStripe 5.3+ Compatibility

1. **Clear browser cache**: Essential when testing component changes
2. **Check browser console**: Look for infinite loops or React errors
3. **Test incrementally**: Add components one by one to isolate issues
4. **Verify button behavior**: Test both "Add to top" and "Add to bottom" functionality
5. **Check element ordering**: Ensure elements appear in correct positions

### Debugging Infinite Loops

Signs of infinite loop issues:
- Browser becomes unresponsive when opening page admin
- Console shows repeated component registration messages
- React DevTools shows components mounting/unmounting rapidly

Common causes:
- `{ force: true }` in component registration
- Circular dependencies in inject patterns
- Components re-registering themselves

### Component Registration Debugging

When components don't appear or inject fails:
1. Check registration order (dependencies first)
2. Verify component names match inject patterns
3. Ensure no typos in component identifiers
4. Test with minimal inject dependencies

## Architecture Overview

### High-Level Architecture

This module extends SilverStripe's Elemental system with a grid layout capability. The architecture consists of three main layers:

1. **Backend PHP Layer**: Extends base elemental models and provides GraphQL resolvers
2. **Frontend React Layer**: Custom React components that integrate with SilverStripe's admin interface  
3. **CSS Framework Layer**: Abstracted CSS framework support (Bootstrap, Tailwind, Bulma)

### Key Architectural Components

#### Backend Architecture
- **ElementalConfig**: Central configuration class for grid settings (column count, CSS framework, default viewport)
- **CSS Framework Interface**: Abstraction layer supporting multiple CSS frameworks via `CSSFrameworkInterface`
- **Extensions**: Extends base SilverStripe classes:
  - `BaseElementExtension`: Adds grid properties to all elements
  - `ElementalAreaExtension`: Adds grid functionality to elemental areas  
  - `ElementalPageExtension`: Page-level grid integration
- **GraphQL Resolver**: `ElementalResolver.php` handles custom insertion logic with `insertAtBottom` parameter
- **Models**: `ElementRow` model for row-based layout grouping

#### Frontend Architecture  
- **React Component System**: Integrates with SilverStripe's Injector pattern
- **Component Registration**: Uses `bundle.js` to register grid-specific React components
- **Toolbar Integration**: Custom buttons (`AddBlockToTopButton`, `AddBlockToBottomButton`) for element insertion
- **Column Sizing**: `ColumnSize` component for responsive column configuration

#### CSS Framework Abstraction
The module supports multiple CSS frameworks through a plugin architecture:
- **BootstrapCSSFramework**: Default Bootstrap 4/5 support  
- **TailwindCSSFramework**: Tailwind CSS integration
- **BulmaCSSFramework**: Bulma framework support
- Framework selection via configuration: `ElementalConfig::$css_framework`

### Critical Integration Points

#### SilverStripe 5.3+ Component Registration
The module uses a specific registration pattern to avoid infinite loops:
```javascript  
// Register dependencies first
Injector.component.register('AddBlockToBottomButton', AddBlockToBottomButton);
Injector.component.register('AddBlockToTopButton', AddBlockToTopButton);
Injector.component.register('ColumnSize', ColumnSize);

// Then register consumers  
Injector.transform('elemental-grid-toolbar', (updater) => {
  updater.component('ElementToolbar', OverruledToolbar);
});
```

#### Element Insertion Logic
Elements can be inserted at top or bottom of lists via:
- Frontend: Button components pass `insertAfterElement` and `insertAtBottom` parameters
- Backend: `ElementalResolver.php` handles the insertion logic, finding appropriate position
- GraphQL: Extended mutation accepts `insertAtBottom` boolean parameter

#### Responsive Grid System
- Column sizes defined per viewport (XS, SM, MD, LG, XL)
- Default viewport configurable via `ElementalConfig::$default_viewport`  
- Column count configurable via `ElementalConfig::$grid_column_count` (default: 12)
- CSS classes generated based on selected framework

## Important Files

### Core JavaScript Files
- `client/src/bundles/bundle.js` - Main component registration (CRITICAL for 5.3+ compatibility)
- `client/src/components/AddBlockToBottomButton.js` - Bottom insertion button
- `client/src/components/AddBlockToTopButton.js` - Top insertion button
- `client/src/components/ElementEditor/Toolbar.js` - Button container component

### Critical Backend Files
- `src/Resolvers/ElementalResolver.php` - Handles `insertAtBottom` logic

### Styling
- `client/src/styles/bundle.scss` - Grid-specific CSS including toolbar button styling

## Future Maintenance

### When Upgrading SilverStripe

1. **Test component registration**: Verify no infinite loops occur
2. **Check inject patterns**: Ensure compatibility with new Injector system
3. **Validate button behavior**: Test element insertion functionality
4. **Review console for errors**: Look for deprecation warnings or errors

### Adding New Components

1. **Keep inject patterns simple**: Minimal dependencies only
2. **Register dependencies first**: Before components that consume them
3. **Test in isolation**: Add one component at a time
4. **Avoid force registration**: Never use `{ force: true }`

## Troubleshooting Guide

### Infinite Loops on Page Admin Load
- Remove `{ force: true }` from component registrations
- Simplify inject patterns
- Check for circular dependencies

### Buttons Not Appearing
- Verify registration order (buttons before toolbar)
- Check component names match inject patterns
- Ensure CSS is properly built (`npm run build`)

### Wrong Insertion Behavior
- Check `insertAfterElement` values in button components
- Verify backend resolver handles `insertAtBottom` correctly
- Test with different element configurations

### CSS Display Issues
- Run `npm run build` after CSS changes
- Check browser cache (hard refresh)
- Verify CSS selectors target correct elements

This documentation should be updated whenever significant changes are made to the SilverStripe compatibility layer or core functionality.
