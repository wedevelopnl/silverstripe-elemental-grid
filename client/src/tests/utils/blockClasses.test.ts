import { describe, it, expect } from 'vitest';
import { buildBlockClasses } from '@/utils/blockClasses';

describe('buildBlockClasses', () => {
  it('builds base and status classes', () => {
    const result = buildBlockClasses('section-block', 'published', {});
    expect(result).toBe('section-block section-block--published');
  });

  it('appends active modifier classes', () => {
    const result = buildBlockClasses('row-block', 'draft', {
      collapsed: true,
      'drop-target': true,
    });
    expect(result).toBe('row-block row-block--draft row-block--collapsed row-block--drop-target');
  });

  it('skips inactive modifier classes', () => {
    const result = buildBlockClasses('column-block', 'modified', {
      collapsed: false,
      hidden: true,
      'drop-target': false,
    });
    expect(result).toBe('column-block column-block--modified column-block--hidden');
  });

  it('handles empty modifiers', () => {
    const result = buildBlockClasses('section-block', 'draft', {});
    expect(result).toBe('section-block section-block--draft');
  });
});
