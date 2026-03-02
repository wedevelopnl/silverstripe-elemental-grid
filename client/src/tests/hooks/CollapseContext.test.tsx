import { renderHook, act } from '@testing-library/react';
import type { ReactNode } from 'react';

import {
  CollapseProvider,
  buildStorageKey,
  useCollapseContext,
} from '@/hooks/CollapseContext';

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

describe('buildStorageKey', () => {
  it('returns a key scoped to the area ID', () => {
    expect(buildStorageKey(42)).toBe('elemental-grid:collapsed:42');
  });

  it('handles different area IDs', () => {
    expect(buildStorageKey(1)).toBe('elemental-grid:collapsed:1');
    expect(buildStorageKey(999)).toBe('elemental-grid:collapsed:999');
  });
});

describe('CollapseProvider', () => {
  function wrapperFor(areaId: number) {
    return function Wrapper({ children }: { readonly children: ReactNode }) {
      return <CollapseProvider areaId={areaId}>{children}</CollapseProvider>;
    };
  }

  it('reads collapsed IDs from localStorage on mount', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', JSON.stringify([1, 2]));

    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    expect(result.current.collapsedIds.has(1)).toBe(true);
    expect(result.current.collapsedIds.has(2)).toBe(true);
    expect(result.current.collapsedIds.has(3)).toBe(false);
  });

  it('scopes storage per area ID', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', JSON.stringify([1]));
    mockStorage.setItem('elemental-grid:collapsed:20', JSON.stringify([2]));

    const area10 = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });
    const area20 = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(20),
    });

    expect(area10.result.current.collapsedIds.has(1)).toBe(true);
    expect(area10.result.current.collapsedIds.has(2)).toBe(false);

    expect(area20.result.current.collapsedIds.has(2)).toBe(true);
    expect(area20.result.current.collapsedIds.has(1)).toBe(false);
  });

  it('toggle adds an element ID to the collapsed set', () => {
    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    act(() => { result.current.toggle(5); });

    expect(result.current.collapsedIds.has(5)).toBe(true);
  });

  it('toggle removes an element ID from the collapsed set', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', JSON.stringify([5]));

    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    act(() => { result.current.toggle(5); });

    expect(result.current.collapsedIds.has(5)).toBe(false);
  });

  it('toggle persists changes to localStorage', () => {
    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    act(() => { result.current.toggle(7); });

    const stored = JSON.parse(
      mockStorage.getItem('elemental-grid:collapsed:10')!,
    ) as number[];
    expect(stored).toContain(7);
  });

  it('handles corrupted localStorage gracefully', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', '{invalid');

    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    expect(result.current.collapsedIds.size).toBe(0);
  });

  it('handles non-array localStorage value gracefully', () => {
    mockStorage.setItem('elemental-grid:collapsed:10', JSON.stringify('string'));

    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    expect(result.current.collapsedIds.size).toBe(0);
  });

  it('handles localStorage write failure gracefully', () => {
    vi.spyOn(mockStorage, 'setItem').mockImplementation(() => {
      throw new Error('QuotaExceededError');
    });

    const { result } = renderHook(() => useCollapseContext(), {
      wrapper: wrapperFor(10),
    });

    // State still updates even when persistence fails
    act(() => { result.current.toggle(1); });
    expect(result.current.collapsedIds.has(1)).toBe(true);
  });
});

describe('useCollapseContext', () => {
  it('throws when used outside CollapseProvider', () => {
    expect(() => {
      renderHook(() => useCollapseContext());
    }).toThrow('useCollapseContext must be used within a CollapseProvider');
  });
});
