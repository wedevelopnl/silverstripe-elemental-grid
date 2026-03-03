import { renderHook, act } from '@testing-library/react';

import {
  useCollapseEnrichment,
  buildStorageKey,
} from '@/hooks/useCollapseEnrichment';
import type { SectionNode, RowNode, ColumnNode } from '@/types/elements';

function createMockStorage(): Storage {
  const store = new Map<string, string>();

  return {
    get length() { return store.size; },
    clear: () => store.clear(),
    getItem: (key: string) => store.get(key) ?? null,
    key: (index: number) => [...store.keys()][index] ?? null,
    removeItem: (key: string) => store.delete(key),
    setItem: (key: string, value: string) => store.set(key, value),
  };
}

let mockStorage: Storage;

beforeEach(() => {
  mockStorage = createMockStorage();
  vi.stubGlobal('localStorage', mockStorage);
});

afterEach(() => {
  vi.unstubAllGlobals();
});

const AREA_ID = 42;

function storageKey(): string {
  return `elemental-grid:collapsed:${String(AREA_ID)}`;
}

function makeColumn(id: number, overrides: Partial<ColumnNode> = {}): ColumnNode {
  return {
    id,
    title: `Column ${id}`,
    blockSchema: {
      typeName: 'Column',
      label: 'Column',
      actions: { edit: `/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'column',
    allowedTypes: null,
    children: null,
    gridSettings: { md: { width: 6, offset: 0, visible: true } },
    ...overrides,
  };
}

function makeRow(id: number, overrides: Partial<RowNode> = {}): RowNode {
  return {
    id,
    title: `Row ${id}`,
    blockSchema: {
      typeName: 'Row',
      label: 'Row',
      actions: { edit: `/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'row',
    allowedTypes: null,
    children: null,
    ...overrides,
  };
}

function makeSection(id: number, overrides: Partial<SectionNode> = {}): SectionNode {
  return {
    id,
    title: `Section ${id}`,
    blockSchema: {
      typeName: 'Section',
      label: 'Section',
      actions: { edit: `/edit/${id}` },
      content: '',
    },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
    containerType: 'section',
    allowedTypes: null,
    children: null,
    ...overrides,
  };
}

describe('buildStorageKey', () => {
  it('returns a key scoped to the area ID', () => {
    expect(buildStorageKey(42)).toBe('elemental-grid:collapsed:42');
  });

  it('handles different area IDs', () => {
    expect(buildStorageKey(1)).toBe('elemental-grid:collapsed:1');
    expect(buildStorageKey(999)).toBe('elemental-grid:collapsed:999');
  });
});

describe('useCollapseEnrichment', () => {
  it('defaults all nodes to expanded when no localStorage data', () => {
    const sections = [makeSection(1)];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(false);
  });

  it('reads collapsed state from localStorage', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([1, 2]));

    const sections = [
      makeSection(1),
      makeSection(2),
      makeSection(3),
    ];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(true);
    expect(result.current[1].isCollapsed).toBe(true);
    expect(result.current[2].isCollapsed).toBe(false);
  });

  it('toggle collapses an expanded node and persists to localStorage', () => {
    const sections = [makeSection(5)];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));
    expect(result.current[0].isCollapsed).toBe(false);

    act(() => { result.current[0].toggle(); });

    expect(result.current[0].isCollapsed).toBe(true);
    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).toContain(5);
  });

  it('toggle expands a collapsed node and removes from localStorage', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([5]));
    const sections = [makeSection(5)];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));
    expect(result.current[0].isCollapsed).toBe(true);

    act(() => { result.current[0].toggle(); });

    expect(result.current[0].isCollapsed).toBe(false);
    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).not.toContain(5);
  });

  it('preserves other IDs when toggling', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([10, 20, 30]));
    const sections = [makeSection(20)];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    act(() => { result.current[0].toggle(); });

    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).toContain(10);
    expect(stored).toContain(30);
    expect(stored).not.toContain(20);
  });

  it('recursively enriches children at every level', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([10, 30]));

    const sections = [
      makeSection(1, {
        children: [
          makeRow(10, {
            children: [makeColumn(30)],
          }),
        ],
      }),
    ];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    const section = result.current[0];
    expect(section.isCollapsed).toBe(false);
    expect(typeof section.toggle).toBe('function');

    const row = section.children![0];
    expect(row.isCollapsed).toBe(true);
    expect(typeof row.toggle).toBe('function');

    const column = row.children![0];
    expect(column.isCollapsed).toBe(true);
    expect(typeof column.toggle).toBe('function');
  });

  it('handles null children', () => {
    const sections = [
      makeSection(1, { children: null }),
    ];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].children).toBeNull();
  });

  it('handles corrupted localStorage gracefully (non-JSON)', () => {
    mockStorage.setItem(storageKey(), 'not-json');

    const sections = [makeSection(1)];
    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(false);
  });

  it('handles corrupted localStorage gracefully (non-array)', () => {
    mockStorage.setItem(storageKey(), JSON.stringify({ foo: 'bar' }));

    const sections = [makeSection(1)];
    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(false);
  });

  it('filters non-number values from localStorage array', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([1, 'two', null, 3]));

    const sections = [makeSection(1), makeSection(3)];
    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(true);
    expect(result.current[1].isCollapsed).toBe(true);
  });

  it('handles localStorage.getItem throwing', () => {
    vi.spyOn(mockStorage, 'getItem').mockImplementation(() => {
      throw new Error('SecurityError');
    });

    const sections = [makeSection(1)];
    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    expect(result.current[0].isCollapsed).toBe(false);
  });

  it('handles localStorage.setItem throwing', () => {
    vi.spyOn(mockStorage, 'setItem').mockImplementation(() => {
      throw new Error('QuotaExceededError');
    });

    const sections = [makeSection(1)];
    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    act(() => { result.current[0].toggle(); });

    expect(result.current[0].isCollapsed).toBe(true);
  });

  it('scopes storage per areaId', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', JSON.stringify([1]));
    mockStorage.setItem('elemental-grid:collapsed:20', JSON.stringify([2]));

    const sections = [makeSection(1), makeSection(2)];

    const area10 = renderHook(() => useCollapseEnrichment(sections, 10));
    const area20 = renderHook(() => useCollapseEnrichment(sections, 20));

    expect(area10.result.current[0].isCollapsed).toBe(true);
    expect(area10.result.current[1].isCollapsed).toBe(false);

    expect(area20.result.current[0].isCollapsed).toBe(false);
    expect(area20.result.current[1].isCollapsed).toBe(true);
  });

  it('returns empty array for empty sections input', () => {
    const { result } = renderHook(() => useCollapseEnrichment([], AREA_ID));

    expect(result.current).toEqual([]);
  });

  it('child toggle updates the correct node', () => {
    const sections = [
      makeSection(1, {
        children: [
          makeRow(10, {
            children: [makeColumn(30)],
          }),
        ],
      }),
    ];

    const { result } = renderHook(() => useCollapseEnrichment(sections, AREA_ID));

    act(() => { result.current[0].children![0].children![0].toggle(); });

    expect(result.current[0].isCollapsed).toBe(false);
    expect(result.current[0].children![0].isCollapsed).toBe(false);
    expect(result.current[0].children![0].children![0].isCollapsed).toBe(true);

    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).toContain(30);
    expect(stored).not.toContain(1);
    expect(stored).not.toContain(10);
  });
});
