import { adapterConfigSchema } from '@/types/adapter';

describe('adapterConfigSchema', () => {
  it('parses valid adapter config', () => {
    const input = {
      viewports: [
        { key: 'xs', label: 'Extra Small' },
        { key: 'md', label: 'Medium' },
      ],
      defaultViewport: 'md',
      columnCount: 12,
      rowClasses: 'row',
      baseWidthClasses: { '1': 'col-1', '2': 'col-2', '12': 'col-12' },
      baseOffsetClasses: { '0': 'offset-0', '1': 'offset-1', '11': 'offset-11' },
    };

    const result = adapterConfigSchema.parse(input);
    expect(result.defaultViewport).toBe('md');
    expect(result.columnCount).toBe(12);
    expect(result.baseWidthClasses['12']).toBe('col-12');
  });

  it('rejects missing required fields', () => {
    expect(() => adapterConfigSchema.parse({})).toThrow();
  });

  it('rejects non-integer columnCount', () => {
    const input = {
      viewports: [],
      defaultViewport: 'md',
      columnCount: 12.5,
      rowClasses: 'row',
      baseWidthClasses: {},
      baseOffsetClasses: {},
    };

    expect(() => adapterConfigSchema.parse(input)).toThrow();
  });

  it('rejects non-positive columnCount', () => {
    const input = {
      viewports: [],
      defaultViewport: 'md',
      columnCount: 0,
      rowClasses: 'row',
      baseWidthClasses: {},
      baseOffsetClasses: {},
    };

    expect(() => adapterConfigSchema.parse(input)).toThrow();
  });
});
