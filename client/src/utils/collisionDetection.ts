import {
  closestCenter,
  type CollisionDetection,
  type DroppableContainer,
} from '@dnd-kit/core';

import { getDraggableType, PARENT_CONTAINER_TYPE } from '@/types/dnd';

/**
 * Filters droppable containers to only those valid for the given active item.
 *
 * A container is valid if:
 * - It has the same type as the active item (sibling reordering)
 * - It has the parent container type of the active item (dropping into parent)
 * - For sections (parent = 'root'), any container with an unparseable ID
 *   (e.g. the root SortableContext whose ID is 'root')
 */
export function filterDroppablesByType(
  activeId: string,
  containers: DroppableContainer[],
): DroppableContainer[] {
  const activeType = getDraggableType(activeId);
  if (activeType === null) return [];

  const parentType = PARENT_CONTAINER_TYPE[activeType];

  return containers.filter((container) => {
    const containerType = getDraggableType(String(container.id));

    if (containerType === activeType) return true;

    if (parentType !== 'root' && containerType === parentType) return true;

    // Sections live under the root-level sortable context, whose droppable
    // ID won't parse as a valid draggable type (e.g. the string 'root').
    if (parentType === 'root' && containerType === null) return true;

    return false;
  });
}

/**
 * Type-aware collision detection strategy for dnd-kit.
 *
 * Restricts drop targets to only hierarchy-valid containers before delegating
 * to `closestCenter` for the actual proximity calculation. This prevents items
 * from being dropped at invalid hierarchy levels (e.g. a row into a column).
 */
export const typedCollisionDetection: CollisionDetection = (args) => {
  const activeType = getDraggableType(String(args.active.id));
  if (activeType === null) return [];

  const filteredContainers = filterDroppablesByType(
    String(args.active.id),
    args.droppableContainers,
  );

  return closestCenter({
    ...args,
    droppableContainers: filteredContainers,
  });
};
