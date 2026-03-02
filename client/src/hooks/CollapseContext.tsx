import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

export interface CollapseContextValue {
  readonly collapsedIds: ReadonlySet<number>;
  readonly toggle: (elementId: number) => void;
}

const CollapseContext = createContext<CollapseContextValue | null>(null);

export function buildStorageKey(areaId: number): string {
  return `elemental-grid:collapsed:${String(areaId)}`;
}

function readCollapsedIds(key: string): ReadonlySet<number> {
  try {
    const raw = localStorage.getItem(key);
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

function writeCollapsedIds(key: string, ids: ReadonlySet<number>): void {
  try {
    localStorage.setItem(key, JSON.stringify([...ids]));
  } catch {
    // QuotaExceededError or SecurityError — silently ignore
  }
}

interface CollapseProviderProps {
  readonly areaId: number;
  readonly children: ReactNode;
}

export function CollapseProvider({ areaId, children }: CollapseProviderProps) {
  const storageKey = buildStorageKey(areaId);

  const [collapsedIds, setCollapsedIds] = useState<ReadonlySet<number>>(
    () => readCollapsedIds(storageKey),
  );

  const toggle = useCallback((elementId: number) => {
    setCollapsedIds((prev) => {
      const next = new Set(prev);

      if (next.has(elementId)) {
        next.delete(elementId);
      } else {
        next.add(elementId);
      }

      writeCollapsedIds(storageKey, next);
      return next;
    });
  }, [storageKey]);

  const value: CollapseContextValue = useMemo(
    () => ({ collapsedIds, toggle }),
    [collapsedIds, toggle],
  );

  return (
    <CollapseContext.Provider value={value}>
      {children}
    </CollapseContext.Provider>
  );
}

export function useCollapseContext(): CollapseContextValue {
  const value = useContext(CollapseContext);
  if (value === null) {
    throw new Error('useCollapseContext must be used within a CollapseProvider');
  }
  return value;
}
