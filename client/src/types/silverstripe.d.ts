/**
 * Type declarations for SilverStripe CMS globals consumed by the grid editor.
 *
 * The admin module exposes shared JS modules as window globals via webpack
 * externals. We access these via `window` rather than ES imports to avoid
 * needing Rollup external mappings for non-React globals.
 */

/* eslint-disable @typescript-eslint/no-explicit-any */

import type { ComponentType } from 'react';

// --- Injector (lib/Injector) ---

export interface InjectorComponentRegistry {
  // Components are registered with their own prop types but retrieved
  // generically — the registry accepts any component signature.
  registerMany(components: Record<string, ComponentType<any>>): void;
}

export interface InjectorContainer {
  component: InjectorComponentRegistry;
}

interface InjectorGlobal {
  /** The singleton Container instance */
  default: InjectorContainer;
  /** Load a component with all registered transforms applied */
  loadComponent(name: string, context?: Record<string, unknown>): ComponentType<any>;
}

// --- jQuery + entwine ---

interface EntwineRules {
  onmatch?(this: JQueryEntwineElement): void;
  onunmatch?(this: JQueryEntwineElement): void;
  [key: string]: unknown;
}

interface JQueryEntwineElement {
  data(key: string): unknown;
  entwine(rules: EntwineRules): void;
  getReactRoot(): import('react-dom/client').Root | null;
  setReactRoot(root: import('react-dom/client').Root | null): void;
  [index: number]: HTMLElement;
}

interface JQueryStatic {
  (selector: string): JQueryEntwineElement;
  entwine(namespace: string, callback: ($: JQueryStatic) => void): void;
}

// --- Window augmentation ---

declare global {
  interface Window {
    Injector: InjectorGlobal;
    jQuery: JQueryStatic;
  }
}
