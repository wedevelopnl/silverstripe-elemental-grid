import type {
  ElementTreeResponse,
  ElementNode,
  ContainerNode,
} from '@/types/elements';
import { isContainerNode } from '@/types/elements';

interface FoundLocation {
  /** 'root' if found in a top-level area array, 'child' if in a container's children */
  kind: 'root' | 'child';
  /** The area key (root) or parent container reference path */
  areaKey: string;
  /** Index within the array where the element was found */
  index: number;
}

/**
 * Searches for a container (by childAreaId) that owns the given target area.
 * Returns the children array of that container, or null if not found.
 */
function findChildrenForArea(
  nodes: ElementNode[],
  targetAreaId: number,
): ElementNode[] | null {
  for (const node of nodes) {
    if (isContainerNode(node) && node.childAreaId === targetAreaId) {
      return node.children ?? [];
    }
    if (isContainerNode(node) && node.children) {
      const found = findChildrenForArea(node.children, targetAreaId);
      if (found) return found;
    }
  }
  return null;
}

/**
 * Finds the location of an element by ID anywhere in the tree.
 * Returns the area key and index, or null if not found.
 */
function findElement(
  tree: ElementTreeResponse,
  elementId: number,
): FoundLocation | null {
  // Check root areas first
  for (const [areaKey, nodes] of Object.entries(tree)) {
    const index = nodes.findIndex((n) => n.id === elementId);
    if (index !== -1) {
      return { kind: 'root', areaKey, index };
    }
  }

  // Recursive search in container children
  for (const nodes of Object.values(tree)) {
    const result = findInChildren(nodes, elementId);
    if (result) return result;
  }

  return null;
}

function findInChildren(
  nodes: ElementNode[],
  elementId: number,
): FoundLocation | null {
  for (const node of nodes) {
    if (!isContainerNode(node) || !node.children) continue;

    const index = node.children.findIndex((c) => c.id === elementId);
    if (index !== -1) {
      return {
        kind: 'child',
        areaKey: String(node.childAreaId),
        index,
      };
    }

    const deeper = findInChildren(node.children, elementId);
    if (deeper) return deeper;
  }
  return null;
}

/**
 * Determines whether the element is already at the desired position,
 * making the reorder a no-op.
 */
function isNoOp(
  tree: ElementTreeResponse,
  elementId: number,
  targetAreaId: number,
  afterElementId: number | null,
  location: FoundLocation,
): boolean {
  const sourceAreaId = Number(location.areaKey);
  if (sourceAreaId !== targetAreaId) return false;

  // Get the sibling list to check current position
  const siblings = getSiblingList(tree, targetAreaId);
  if (!siblings) return false;

  if (afterElementId === null) {
    // Target: first position — already there if element is at index 0
    return siblings[0]?.id === elementId;
  }

  // Target: after afterElementId — find afterElementId's index
  const afterIndex = siblings.findIndex((n) => n.id === afterElementId);
  if (afterIndex === -1) return false;

  // The element should be at afterIndex + 1
  return siblings[afterIndex + 1]?.id === elementId;
}

/**
 * Gets the sibling list for a given area ID (either root array or container children).
 */
function getSiblingList(
  tree: ElementTreeResponse,
  areaId: number,
): ElementNode[] | null {
  const areaKey = String(areaId);
  if (areaKey in tree) return tree[areaKey];

  for (const nodes of Object.values(tree)) {
    const found = findChildrenForArea(nodes, areaId);
    if (found) return found;
  }
  return null;
}

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
  const location = findElement(tree, elementId);
  if (!location) return tree;

  if (isNoOp(tree, elementId, targetAreaId, afterElementId, location)) {
    return tree;
  }

  // Deep clone the tree so we can mutate it safely
  const cloned = structuredClone(tree);

  // Remove element from its current position
  const element = removeElement(cloned, elementId, location);
  if (!element) return tree;

  // Insert at new position
  const inserted = insertElement(cloned, element, targetAreaId, afterElementId);
  if (!inserted) return tree;

  // Preserve references for unaffected root areas
  const result: ElementTreeResponse = {};
  for (const key of Object.keys(tree)) {
    if (isAreaAffected(tree, key, targetAreaId, location)) {
      result[key] = cloned[key];
    } else {
      result[key] = tree[key];
    }
  }

  return result;
}

/**
 * Checks whether a root area key is affected by the reorder operation.
 */
function isAreaAffected(
  tree: ElementTreeResponse,
  areaKey: string,
  targetAreaId: number,
  sourceLocation: FoundLocation,
): boolean {
  const areaId = Number(areaKey);

  // Root area directly contains the source or target
  if (sourceLocation.kind === 'root' && sourceLocation.areaKey === areaKey) {
    return true;
  }
  if (areaId === targetAreaId) return true;

  // Check if any container in this root area contains the source or target area
  const nodes = tree[areaKey];

  const sourceAreaId = Number(sourceLocation.areaKey);
  if (containsArea(nodes, sourceAreaId)) return true;
  if (containsArea(nodes, targetAreaId)) return true;

  return false;
}

function containsArea(nodes: ElementNode[], areaId: number): boolean {
  for (const node of nodes) {
    if (isContainerNode(node)) {
      if (node.childAreaId === areaId) return true;
      if (node.children && containsArea(node.children, areaId)) return true;
    }
  }
  return false;
}

/**
 * Removes an element from the cloned tree and returns it.
 */
function removeElement(
  cloned: ElementTreeResponse,
  elementId: number,
  location: FoundLocation,
): ElementNode | null {
  if (location.kind === 'root') {
    const arr = cloned[location.areaKey];
    const [element] = arr.splice(location.index, 1);
    return element ?? null;
  }

  // Remove from container children
  for (const nodes of Object.values(cloned)) {
    const result = removeFromChildren(nodes, elementId);
    if (result) return result;
  }
  return null;
}

function removeFromChildren(
  nodes: ElementNode[],
  elementId: number,
): ElementNode | null {
  for (const node of nodes) {
    if (!isContainerNode(node) || !node.children) continue;

    const index = node.children.findIndex((c) => c.id === elementId);
    if (index !== -1) {
      const [removed] = node.children.splice(index, 1);
      return removed ?? null;
    }

    const deeper = removeFromChildren(node.children, elementId);
    if (deeper) return deeper;
  }
  return null;
}

/**
 * Inserts an element at the target position in the cloned tree.
 */
function insertElement(
  cloned: ElementTreeResponse,
  element: ElementNode,
  targetAreaId: number,
  afterElementId: number | null,
): boolean {
  const areaKey = String(targetAreaId);

  // Check root areas
  if (areaKey in cloned) {
    const arr = cloned[areaKey];
    insertIntoArray(arr, element, afterElementId);
    return true;
  }

  // Check container children
  for (const nodes of Object.values(cloned)) {
    if (insertIntoContainerChildren(nodes, element, targetAreaId, afterElementId)) {
      return true;
    }
  }

  return false;
}

function insertIntoContainerChildren(
  nodes: ElementNode[],
  element: ElementNode,
  targetAreaId: number,
  afterElementId: number | null,
): boolean {
  for (const node of nodes) {
    if (!isContainerNode(node)) continue;

    if (node.childAreaId === targetAreaId) {
      if (!node.children) {
        (node as ContainerNode).children = [] as unknown as ContainerNode['children'];
      }
      insertIntoArray(node.children!, element, afterElementId);
      return true;
    }

    if (node.children) {
      if (insertIntoContainerChildren(node.children, element, targetAreaId, afterElementId)) {
        return true;
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
    // Fallback: append to end
    arr.push(element);
    return;
  }

  arr.splice(afterIndex + 1, 0, element);
}
