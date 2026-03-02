import { getElementStatus } from '@/types/status';
import type { ElementStatus } from '@/types/status';

const draft = { text: 'Draft', title: 'Item has not been published yet' };
const modified = { text: 'Modified', title: 'Item has unpublished changes' };

describe('getElementStatus', () => {
  it('returns "draft" when addedtodraft flag is present', () => {
    expect(getElementStatus({ addedtodraft: draft })).toBe<ElementStatus>('draft');
  });

  it('returns "published" when statusFlags is empty', () => {
    expect(getElementStatus({})).toBe<ElementStatus>('published');
  });

  it('returns "modified" when modified flag is present', () => {
    expect(getElementStatus({ modified })).toBe<ElementStatus>('modified');
  });

  it('returns "draft" when both addedtodraft and modified flags are present', () => {
    // addedtodraft takes priority over modified
    expect(getElementStatus({ addedtodraft: draft, modified })).toBe<ElementStatus>('draft');
  });
});
