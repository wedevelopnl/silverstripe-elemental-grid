import {
  getViewports,
  getDefaultViewport,
  getColumnCount,
  getRowClasses,
  getWidthClass,
  getOffsetClass,
} from '@/utils/gridAdapter';

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

describe('gridAdapter', () => {
  it('returns the list of viewports from adapter config', () => {
    const viewports = getViewports();
    expect(viewports).toHaveLength(6);
    expect(viewports[0].key).toBe('xs');
  });

  it('returns the default viewport key', () => {
    expect(getDefaultViewport()).toBe('md');
  });

  it('returns the column count from adapter config', () => {
    expect(getColumnCount()).toBe(12);
  });

  it('returns row classes from adapter config', () => {
    expect(getRowClasses()).toBe('row');
  });

  it('looks up base width class by column width', () => {
    expect(getWidthClass(6)).toBe('col-6');
  });

  it('looks up base offset class by offset value', () => {
    expect(getOffsetClass(3)).toBe('offset-3');
  });

  it('returns the offset-0 class for zero offset', () => {
    expect(getOffsetClass(0)).toBe('offset-0');
  });

  it('returns empty string for unmapped width key', () => {
    expect(getWidthClass(99)).toBe('');
  });

  it('returns empty string for unmapped offset key', () => {
    expect(getOffsetClass(99)).toBe('');
  });
});
