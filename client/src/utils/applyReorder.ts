import {
  isContainerNode,
  type ElementTreeResponse,
  type ElementNode,
} from '@/types/elements';
import { buildMaps } from '@/hooks/useElementMaps';

/**
 * Applies a reorder operation to the element tree, returning a new tree
 * with the element moved to the specified position.
 *
 * Returns the SAME reference if the element is already at the target position (no-op),
 * if the element is not found, or if the target parent does not exist.
 */
export function applyReorder(
  tree: ElementTreeResponse,
  elementId: number,
  targetParentId: number,
  afterElementId: number | null,
): ElementTreeResponse {
  const maps = buildMaps(tree);

  const element = maps.nodeMap.get(elementId);
  if (!element) return tree;

  const sourceParentId = element.parentId;
  const sourceChildren = maps.childrenByParentId.get(sourceParentId);
  if (!sourceChildren) return tree;

  const sourceIndex = sourceChildren.findIndex((n) => n.id === elementId);
  if (sourceIndex === -1) return tree;

  // Check target parent exists
  const targetChildren = maps.childrenByParentId.get(targetParentId);
  if (!targetChildren && !Object.prototype.hasOwnProperty.call(tree, String(targetParentId))) {
    // Target parent must exist either as root key or as a container's ID
    if (!targetChildren) return tree;
  }

  // No-op detection
  if (isNoOp(sourceParentId, sourceIndex, sourceChildren, targetParentId, afterElementId)) {
    return tree;
  }

  // Deep clone the tree, then build maps from the clone so references point into the clone
  const cloned = structuredClone(tree);
  const clonedMaps = buildMaps(cloned);

  // Remove element from its current position in the clone
  const clonedSourceChildren = clonedMaps.childrenByParentId.get(sourceParentId);
  if (!clonedSourceChildren) return tree;

  const clonedSourceIndex = clonedSourceChildren.findIndex((n) => n.id === elementId);
  if (clonedSourceIndex === -1) return tree;

  const [movedElement] = clonedSourceChildren.splice(clonedSourceIndex, 1);

  // Update parentId on the moved element if crossing parents
  if (sourceParentId !== targetParentId) {
    (movedElement as { parentId: number }).parentId = targetParentId;
  }

  // Insert at new position
  const clonedTargetChildren = clonedMaps.childrenByParentId.get(targetParentId);
  if (!clonedTargetChildren) return tree;

  insertIntoArray(clonedTargetChildren, movedElement, afterElementId);

  // Preserve references for unaffected root trees
  const result: ElementTreeResponse = {};
  for (const key of Object.keys(tree)) {
    if (isTreeAffected(maps, key, sourceParentId, targetParentId)) {
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
  sourceParentId: number,
  sourceIndex: number,
  sourceChildren: ElementNode[],
  targetParentId: number,
  afterElementId: number | null,
): boolean {
  if (sourceParentId !== targetParentId) return false;

  if (afterElementId === null) {
    return sourceIndex === 0;
  }

  const afterIndex = sourceChildren.findIndex((n) => n.id === afterElementId);
  if (afterIndex === -1) return false;

  return afterIndex + 1 === sourceIndex;
}

/**
 * Checks whether a root tree key is affected by the reorder operation.
 * A root key's subtree is affected if the source or target parent is
 * the root key itself, or is a container node within its subtree.
 */
function isTreeAffected(
  maps: ReturnType<typeof buildMaps>,
  rootKey: string,
  sourceParentId: number,
  targetParentId: number,
): boolean {
  const rootId = Number(rootKey);

  // Direct match: root IS the source or target parent
  if (rootId === sourceParentId || rootId === targetParentId) return true;

  // Check if any container in this root tree is the source or target parent
  const rootNodes = maps.childrenByParentId.get(rootId);
  if (!rootNodes) return false;

  if (containsParent(rootNodes, sourceParentId)) return true;
  if (containsParent(rootNodes, targetParentId)) return true;

  return false;
}

/**
 * Checks if any container node in the given array (or its descendants)
 * has an ID matching the target parent ID.
 */
function containsParent(
  nodes: ElementNode[],
  parentId: number,
): boolean {
  for (const node of nodes) {
    if (isContainerNode(node)) {
      if (node.id === parentId) return true;
      if (node.children) {
        if (containsParent(node.children, parentId)) return true;
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
