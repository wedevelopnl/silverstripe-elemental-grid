# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## **🚨 CRITICAL DEVELOPMENT RULE - ALWAYS CHECK VENDOR CODE FIRST**

**Before making ANY changes to this module, you MUST examine the relevant vendor code to understand the full context.**

### Why This Rule Exists
This module extends complex third-party systems (SilverStripe Elemental) that have their own implementation patterns, class names, and architectural decisions. Making assumptions about how these systems work leads to:
- Broken functionality due to incorrect assumptions
- Wasted time implementing solutions that conflict with the underlying system
- CSS/JavaScript that targets non-existent classes or components
- Integration issues that are difficult to debug

### Required Vendor Code Review Process
1. **Identify the relevant vendor directories**: `vendor/dnadesign/silverstripe-elemental/`, `vendor/silverstripe/`
2. **Examine the actual implementation**: Look at the real CSS classes, component names, and integration patterns
3. **Understand the intended workflow**: How does the vendor system expect to be extended?
4. **Document your findings**: Note any discrepancies between assumptions and reality
5. **Design solutions that work WITH the vendor system, not against it**

### Example: Drag/Drop System Investigation
- **Wrong approach**: Assume CSS classes like `.is-dragged-top` exist and build solutions around them
- **Correct approach**: Examine `vendor/dnadesign/silverstripe-elemental/client/src/components/ElementEditor/` to find the actual classes (`.element-editor__element--dragging`, `.elemental-editor-drag-indicator`) and understand how the system really works

**This rule is non-negotiable and must be followed for every feature modification or bug fix.**

## Development Workflow Rules

### Rule: Linting Errors
- When encountering linting errors NEVER take a shortcut and try to build without resolving the linting errors first.

[Rest of the existing content remains unchanged]