import { deriveElementStatus } from '@/types/status';
import type { ElementStatus } from '@/types/status';

describe('deriveElementStatus', () => {
  it('returns "draft" when not published', () => {
    expect(deriveElementStatus(false, false)).toBe<ElementStatus>('draft');
  });

  it('returns "published" when published and live version matches', () => {
    expect(deriveElementStatus(true, true)).toBe<ElementStatus>('published');
  });

  it('returns "modified" when published but live version differs', () => {
    expect(deriveElementStatus(true, false)).toBe<ElementStatus>('modified');
  });

  it('returns "draft" when not published even if isLiveVersion is true', () => {
    // Edge case: isLiveVersion=true is meaningless when unpublished
    expect(deriveElementStatus(false, true)).toBe<ElementStatus>('draft');
  });
});
