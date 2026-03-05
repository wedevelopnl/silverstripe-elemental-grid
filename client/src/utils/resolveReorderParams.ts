import { parseDraggableId } from '@/types/dnd';
import type { ReorderElementParams } from '@/api/endpoints';

export interface ReorderContext {
  /** Composite dnd-kit ID, e.g. 'row-17' */
  activeId: string;
  /** The target parent ID where the item is being dropped */
  overContainerParentId: number;
  /** Insertion index in the target container */
  overIndex: number;
  /** Ordered composite IDs of items in the target container (reflects final order) */
  containerItems: string[];
  /** Parent ID of the source container */
  sourceContainerParentId: number;
  /** Original index in the source container */
  sourceIndex: number;
}

/**
 * Maps dnd-kit event context to the backend API's reorder parameters.
 *
 * Returns null if the active ID is unparseable or the move is a no-op
 * (same container and same index).
 */
export function resolveReorderParams(
  context: ReorderContext,
): ReorderElementParams | null {
  const parsed = parseDraggableId(context.activeId);
  if (!parsed) return null;

  // No-op: same container, same index
  if (
    context.sourceContainerParentId === context.overContainerParentId &&
    context.sourceIndex === context.overIndex
  ) {
    return null;
  }

  const afterElementId = resolveAfterElementId(context, parsed.id);

  return {
    elementID: parsed.id,
    targetParentId: context.overContainerParentId,
    afterElementID: afterElementId,
  };
}

/**
 * Determines the afterElementID by looking at the item before the insertion
 * index in containerItems, skipping the active item itself.
 */
function resolveAfterElementId(
  context: ReorderContext,
  activeElementId: number,
): number | null {
  // Walk backwards from overIndex - 1 to find the first valid, non-active item
  for (let i = context.overIndex - 1; i >= 0; i--) {
    const item = context.containerItems[i];
    const parsed = parseDraggableId(item);
    if (!parsed) continue;
    if (parsed.id === activeElementId) continue;
    return parsed.id;
  }

  return null;
}
