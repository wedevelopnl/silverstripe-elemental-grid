---
description: TypeScript and React frontend conventions
applyTo: "**/*.{ts,tsx}"
---

# TypeScript & React Conventions

## Stack

- React 18, TypeScript 5.9, Vite 7, SCSS
- dnd-kit for drag & drop
- TanStack Query for data fetching
- Zod for runtime validation and schema definitions

## Structure

- `client/src/` is the frontend root
- `@` path alias maps to `client/src` (configured in `vite.config.ts` and `tsconfig.json`)
- Entry points in `client/src/bundles/`
- SilverStripe CMS integration via entwine and Injector in `client/src/bridge/`

## Testing

- Vitest + React Testing Library with jsdom environment
- Test files in `client/src/tests/`
- Stryker for mutation testing
