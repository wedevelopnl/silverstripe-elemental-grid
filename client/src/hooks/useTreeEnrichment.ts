import { useCallback, useMemo, useState } from 'react';
import type { SectionNode, RowNode, ColumnNode, SimpleElementNode } from '@/types/elements';
import type {
  EnrichedSectionNode,
  EnrichedRowNode,
  EnrichedColumnNode,
  EnrichedSimpleElementNode,
} from '@/types/enriched';
import { buildDraggableId, getDraggableTypeForNode } from '@/types/dnd';

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

function enrichElement(element: SimpleElementNode): EnrichedSimpleElementNode {
  return {
    ...element,
    sortableId: buildDraggableId(getDraggableTypeForNode(element), element.id),
  };
}

function enrichColumn(
  column: ColumnNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedColumnNode {
  const enrichedChildren = column.children?.map(enrichElement) ?? null;
  return {
    ...column,
    sortableId: buildDraggableId(getDraggableTypeForNode(column), column.id),
    childSortableIds: enrichedChildren?.map((c) => c.sortableId) ?? [],
    isCollapsed: collapsedIds.has(column.id),
    toggle: () => { toggle(column.id); },
    children: enrichedChildren,
  };
}

function enrichRow(
  row: RowNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedRowNode {
  const enrichedChildren = row.children?.map((col) => enrichColumn(col, collapsedIds, toggle)) ?? null;
  return {
    ...row,
    sortableId: buildDraggableId(getDraggableTypeForNode(row), row.id),
    childSortableIds: enrichedChildren?.map((c) => c.sortableId) ?? [],
    isCollapsed: collapsedIds.has(row.id),
    toggle: () => { toggle(row.id); },
    children: enrichedChildren,
  };
}

function enrichSection(
  section: SectionNode,
  collapsedIds: ReadonlySet<number>,
  toggle: (elementId: number) => void,
): EnrichedSectionNode {
  const enrichedChildren = section.children?.map((row) => enrichRow(row, collapsedIds, toggle)) ?? null;
  return {
    ...section,
    sortableId: buildDraggableId(getDraggableTypeForNode(section), section.id),
    childSortableIds: enrichedChildren?.map((c) => c.sortableId) ?? [],
    isCollapsed: collapsedIds.has(section.id),
    toggle: () => { toggle(section.id); },
    children: enrichedChildren,
  };
}

export function useTreeEnrichment(
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
