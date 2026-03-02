import { useState, useCallback } from 'react';

const STORAGE_KEY = 'elemental-grid:collapsed';

export interface CollapseState {
  readonly isCollapsed: boolean;
  readonly toggle: () => void;
}

function readCollapsedIds(): ReadonlySet<number> {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw === null) {
      return new Set();
    }

    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      return new Set();
    }

    return new Set(parsed.filter((v): v is number => typeof v === 'number'));
  } catch {
    return new Set();
  }
}

function writeCollapsedIds(ids: ReadonlySet<number>): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...ids]));
  } catch {
    // QuotaExceededError or SecurityError — silently ignore
  }
}

export function useCollapse(elementId: number): CollapseState {
  const [isCollapsed, setIsCollapsed] = useState(
    () => readCollapsedIds().has(elementId),
  );

  const toggle = useCallback(() => {
    setIsCollapsed((prev) => {
      const ids = new Set(readCollapsedIds());
      const next = !prev;

      if (next) {
        ids.add(elementId);
      } else {
        ids.delete(elementId);
      }

      writeCollapsedIds(ids);
      return next;
    });
  }, [elementId]);

  return { isCollapsed, toggle };
}
