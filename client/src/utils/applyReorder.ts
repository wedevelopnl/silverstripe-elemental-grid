import type {
  ElementTreeResponse,
  ElementNode,
} from '@/types/elements';
import { buildMaps } from '@/hooks/useElementMaps';

/**
 * Applies a reorder operation to the element tree, returning a new tree
 * with the element moved to the specified position.
 *
 * Returns the SAME reference if the element is already at the target position (no-op),
 * if the element is not found, or if the target area does not exist.
 */
export function applyReorder(
  tree: ElementTreeResponse,
  elementId: number,
  targetAreaId: number,
  afterElementId: number | null,
): ElementTreeResponse {
  const maps = buildMaps(tree);

  const element = maps.nodeMap.get(elementId);
  if (!element) return tree;

  const sourceAreaId = element.parentAreaId;
  const sourceChildren = maps.childrenByAreaId.get(sourceAreaId);
  if (!sourceChildren) return tree;

  const sourceIndex = sourceChildren.findIndex((n) => n.id === elementId);
  if (sourceIndex === -1) return tree;

  // Check target area exists
  const targetChildren = maps.childrenByAreaId.get(targetAreaId);
  if (!targetChildren && !Object.prototype.hasOwnProperty.call(tree, String(targetAreaId))) {
    // Target area must exist either as root key or as a container's childAreaId
    if (!targetChildren) return tree;
  }

  // No-op detection
  if (isNoOp(sourceAreaId, sourceIndex, sourceChildren, targetAreaId, afterElementId)) {
    return tree;
  }

  // Deep clone the tree, then build maps from the clone so references point into the clone
  const cloned = structuredClone(tree);
  const clonedMaps = buildMaps(cloned);

  // Remove element from its current position in the clone
  const clonedSourceChildren = clonedMaps.childrenByAreaId.get(sourceAreaId);
  if (!clonedSourceChildren) return tree;

  const clonedSourceIndex = clonedSourceChildren.findIndex((n) => n.id === elementId);
  if (clonedSourceIndex === -1) return tree;

  const [movedElement] = clonedSourceChildren.splice(clonedSourceIndex, 1);

  // Update parentAreaId on the moved element if crossing areas
  if (sourceAreaId !== targetAreaId) {
    (movedElement as { parentAreaId: number }).parentAreaId = targetAreaId;
  }

  // Insert at new position
  const clonedTargetChildren = clonedMaps.childrenByAreaId.get(targetAreaId);
  if (!clonedTargetChildren) return tree;

  insertIntoArray(clonedTargetChildren, movedElement, afterElementId);

  // Preserve references for unaffected root areas
  const result: ElementTreeResponse = {};
  for (const key of Object.keys(tree)) {
    if (isAreaAffected(maps, key, sourceAreaId, targetAreaId)) {
      result[key] = cloned[key];
    } else {
      result[key] = tree[key];
    }
  }

  return result;
}

/**
 * Determines whether the element is already at the desired position.
 */
function isNoOp(
  sourceAreaId: number,
  sourceIndex: number,
  sourceChildren: ElementNode[],
  targetAreaId: number,
  afterElementId: number | null,
): boolean {
  if (sourceAreaId !== targetAreaId) return false;

  if (afterElementId === null) {
    return sourceIndex === 0;
  }

  const afterIndex = sourceChildren.findIndex((n) => n.id === afterElementId);
  if (afterIndex === -1) return false;

  return afterIndex + 1 === sourceIndex;
}

/**
 * Checks whether a root area key is affected by the reorder operation.
 * Uses the maps to check containment via parentAreaId chain instead of recursion.
 */
function isAreaAffected(
  maps: ReturnType<typeof buildMaps>,
  areaKey: string,
  sourceAreaId: number,
  targetAreaId: number,
): boolean {
  const rootAreaId = Number(areaKey);

  // Direct match: root area IS the source or target
  if (rootAreaId === sourceAreaId || rootAreaId === targetAreaId) return true;

  // Check if any container in this root area owns the source or target area
  const rootNodes = maps.childrenByAreaId.get(rootAreaId);
  if (!rootNodes) return false;

  if (containsArea(rootNodes, sourceAreaId, maps)) return true;
  if (containsArea(rootNodes, targetAreaId, maps)) return true;

  return false;
}

/**
 * Checks if any node in the given array (or its descendants) has a childAreaId
 * matching the target area.
 */
function containsArea(
  nodes: ElementNode[],
  areaId: number,
  maps: ReturnType<typeof buildMaps>,
): boolean {
  for (const node of nodes) {
    if ('childAreaId' in node) {
      const containerNode = node as { childAreaId: number; children?: ElementNode[] | null };
      if (containerNode.childAreaId === areaId) return true;
      if (containerNode.children) {
        if (containsArea(containerNode.children, areaId, maps)) return true;
      }
    }
  }
  return false;
}

function insertIntoArray(
  arr: ElementNode[],
  element: ElementNode,
  afterElementId: number | null,
): void {
  if (afterElementId === null) {
    arr.unshift(element);
    return;
  }

  const afterIndex = arr.findIndex((n) => n.id === afterElementId);
  if (afterIndex === -1) {
    arr.push(element);
    return;
  }

  arr.splice(afterIndex + 1, 0, element);
}
