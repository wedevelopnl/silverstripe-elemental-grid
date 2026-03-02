import { renderHook, act } from '@testing-library/react';
import type { ReactNode } from 'react';

import { CollapseProvider } from '@/hooks/CollapseContext';
import { useCollapse } from '@/hooks/useCollapse';

const AREA_ID = 42;

function storageKey(): string {
  return `elemental-grid:collapsed:${String(AREA_ID)}`;
}

function wrapper({ children }: { readonly children: ReactNode }) {
  return <CollapseProvider areaId={AREA_ID}>{children}</CollapseProvider>;
}

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

describe('useCollapse', () => {
  it('defaults to expanded when element ID is not in localStorage', () => {
    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });

  it('reads initial collapsed state from localStorage', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([1, 2, 3]));

    const { result } = renderHook(() => useCollapse(2), { wrapper });
    expect(result.current.isCollapsed).toBe(true);
  });

  it('returns expanded for element ID not in localStorage set', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([1, 2, 3]));

    const { result } = renderHook(() => useCollapse(99), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });

  it('toggle() collapses an expanded element and persists to localStorage', () => {
    const { result } = renderHook(() => useCollapse(5), { wrapper });
    expect(result.current.isCollapsed).toBe(false);

    act(() => { result.current.toggle(); });

    expect(result.current.isCollapsed).toBe(true);
    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).toContain(5);
  });

  it('toggle() expands a collapsed element and removes from localStorage', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([5]));

    const { result } = renderHook(() => useCollapse(5), { wrapper });
    expect(result.current.isCollapsed).toBe(true);

    act(() => { result.current.toggle(); });

    expect(result.current.isCollapsed).toBe(false);
    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).not.toContain(5);
  });

  it('preserves other element IDs when toggling', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([10, 20, 30]));

    const { result } = renderHook(() => useCollapse(20), { wrapper });

    act(() => { result.current.toggle(); });

    const stored = JSON.parse(mockStorage.getItem(storageKey())!) as number[];
    expect(stored).toContain(10);
    expect(stored).toContain(30);
    expect(stored).not.toContain(20);
  });

  it('handles corrupted localStorage gracefully (non-JSON)', () => {
    mockStorage.setItem(storageKey(), 'not-json');

    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });

  it('handles corrupted localStorage gracefully (non-array)', () => {
    mockStorage.setItem(storageKey(), JSON.stringify({ foo: 'bar' }));

    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });

  it('handles missing localStorage key gracefully', () => {
    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);

    act(() => { result.current.toggle(); });

    expect(result.current.isCollapsed).toBe(true);
  });

  it('filters non-number values from localStorage array', () => {
    mockStorage.setItem(storageKey(), JSON.stringify([1, 'two', null, 3]));

    const hook1 = renderHook(() => useCollapse(1), { wrapper });
    const hook3 = renderHook(() => useCollapse(3), { wrapper });

    expect(hook1.result.current.isCollapsed).toBe(true);
    expect(hook3.result.current.isCollapsed).toBe(true);
  });

  it('handles localStorage.getItem throwing', () => {
    vi.spyOn(mockStorage, 'getItem').mockImplementation(() => {
      throw new Error('SecurityError');
    });

    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });

  it('handles localStorage.setItem throwing', () => {
    vi.spyOn(mockStorage, 'setItem').mockImplementation(() => {
      throw new Error('QuotaExceededError');
    });

    const { result } = renderHook(() => useCollapse(1), { wrapper });

    // Should not throw — toggle gracefully handles write failure
    act(() => { result.current.toggle(); });

    expect(result.current.isCollapsed).toBe(true);
  });

  it('throws when used outside CollapseProvider', () => {
    expect(() => {
      renderHook(() => useCollapse(1));
    }).toThrow('useCollapseContext must be used within a CollapseProvider');
  });

  it('ignores legacy global key', () => {
    mockStorage.setItem('elemental-grid:collapsed', JSON.stringify([1, 2, 3]));

    const { result } = renderHook(() => useCollapse(1), { wrapper });
    expect(result.current.isCollapsed).toBe(false);
  });
});
