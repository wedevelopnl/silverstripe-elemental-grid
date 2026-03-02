import type { StatusFlags } from './elements';

export type ElementStatus = 'draft' | 'published' | 'modified';

export function getElementStatus(statusFlags: StatusFlags): ElementStatus {
  if (statusFlags.addedtodraft !== undefined) {
    return 'draft';
  }
  if (statusFlags.modified !== undefined) {
    return 'modified';
  }
  return 'published';
}
