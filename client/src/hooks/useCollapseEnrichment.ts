import { useCallback, useMemo, useState } from 'react';
import type { SectionNode, RowNode, ColumnNode } from '@/types/elements';
import type {
  EnrichedSectionNode,
  EnrichedRowNode,
  EnrichedColumnNode,
} from '@/types/enriched';

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

function enrichColumn(
  column: ColumnNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedColumnNode {
  return {
    ...column,
    isCollapsed: collapsedIds.has(column.id),
    toggle: () => { toggle(column.id); },
  };
}

function enrichRow(
  row: RowNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedRowNode {
  return {
    ...row,
    isCollapsed: collapsedIds.has(row.id),
    toggle: () => { toggle(row.id); },
    children: row.children?.map((col) => enrichColumn(col, collapsedIds, toggle)) ?? null,
  };
}

function enrichSection(
  section: SectionNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedSectionNode {
  return {
    ...section,
    isCollapsed: collapsedIds.has(section.id),
    toggle: () => { toggle(section.id); },
    children: section.children?.map((row) => enrichRow(row, collapsedIds, toggle)) ?? null,
  };
}

export function useCollapseEnrichment(
  sections: readonly SectionNode[],
  areaId: number,
): readonly EnrichedSectionNode[] {
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

  return useMemo(
    () => sections.map((section) => enrichSection(section, collapsedIds, toggle)),
    [sections, collapsedIds, toggle],
  );
}
