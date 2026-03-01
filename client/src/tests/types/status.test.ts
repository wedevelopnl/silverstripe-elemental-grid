import { getElementStatus } from '@/types/status';
import type { ElementStatus } from '@/types/status';

describe('getElementStatus', () => {
  it('returns "draft" when addedtodraft flag is present', () => {
    expect(getElementStatus({ addedtodraft: 'Draft' })).toBe<ElementStatus>('draft');
  });

  it('returns "published" when statusFlags is empty', () => {
    expect(getElementStatus({})).toBe<ElementStatus>('published');
  });

  it('returns "modified" when modified flag is present', () => {
    expect(getElementStatus({ modified: 'Modified' })).toBe<ElementStatus>('modified');
  });

  it('returns "draft" when both addedtodraft and modified flags are present', () => {
    // addedtodraft takes priority over modified
    expect(getElementStatus({ addedtodraft: 'Draft', modified: 'Modified' })).toBe<ElementStatus>('draft');
  });
});
