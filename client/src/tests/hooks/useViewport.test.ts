import { renderHook, act } from '@testing-library/react';
import { useViewport } from '@/hooks/useViewport';

vi.mock('@/api/config', () => ({
  getAdapterConfig: () => ({
    viewports: [
      { key: 'xs', label: 'Extra Small', minWidth: null },
      { key: 'sm', label: 'Small', minWidth: 576 },
      { key: 'md', label: 'Medium', minWidth: 768 },
      { key: 'lg', label: 'Large', minWidth: 992 },
      { key: 'xl', label: 'Extra Large', minWidth: 1200 },
      { key: 'xxl', label: 'Extra Extra Large', minWidth: 1400 },
    ],
    defaultViewport: 'md',
    columnCount: 12,
    rowClasses: 'row',
    baseWidthClasses: Object.fromEntries(
      Array.from({ length: 12 }, (_, i) => [String(i + 1), `col-${i + 1}`]),
    ),
    baseOffsetClasses: Object.fromEntries(
      Array.from({ length: 12 }, (_, i) => [String(i), `offset-${i}`]),
    ),
  }),
}));

describe('useViewport', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('initializes with the default viewport key from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.activeViewport).toBe('md');
  });

  it('provides the list of viewports from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.viewports).toHaveLength(6);
    expect(result.current.viewports[0].key).toBe('xs');
  });

  it('updates the active viewport when setActiveViewport is called', () => {
    const { result } = renderHook(() => useViewport());

    act(() => {
      result.current.setActiveViewport('lg');
    });

    expect(result.current.activeViewport).toBe('lg');
  });

  it('provides the column count from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.columnCount).toBe(12);
  });

  it('provides row classes from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.rowClasses).toBe('row');
  });

  it('looks up base width class from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getWidthClass(6)).toBe('col-6');
  });

  it('looks up base offset class from adapter config', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getOffsetClass(3)).toBe('offset-3');
  });

  it('returns the offset-0 class for zero offset', () => {
    const { result } = renderHook(() => useViewport());
    expect(result.current.getOffsetClass(0)).toBe('offset-0');
  });
});
