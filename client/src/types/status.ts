export type ElementStatus = 'draft' | 'published' | 'modified';

/**
 * Derives the display status from the API's publish/version flags.
 *
 * Priority: unpublished elements are always 'draft', regardless of isLiveVersion.
 * Published elements are 'published' only when the live version matches the draft.
 */
export function deriveElementStatus(
  isPublished: boolean,
  isLiveVersion: boolean,
): ElementStatus {
  if (!isPublished) {
    return 'draft';
  }

  return isLiveVersion ? 'published' : 'modified';
}
