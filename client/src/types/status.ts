export type ElementStatus = 'draft' | 'published' | 'modified';

export function getElementStatus(
  statusFlags: Record<string, unknown>,
): ElementStatus {
  if ('addedtodraft' in statusFlags) {
    return 'draft';
  }
  if ('modified' in statusFlags) {
    return 'modified';
  }
  return 'published';
}
