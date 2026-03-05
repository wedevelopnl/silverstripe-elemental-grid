import { useMemo } from 'react';
import { isContainerNode } from '@/types/elements';
import type { ElementNode, ElementTreeResponse } from '@/types/elements';

export interface ElementMaps {
  nodeMap: Map<number, ElementNode>;
  childrenByParentId: Map<number, ElementNode[]>;
}

/**
 * Walks the tree once, populating both lookup maps simultaneously.
 */
function walkNodes(
  nodes: ElementNode[],
  nodeMap: Map<number, ElementNode>,
  childrenByParentId: Map<number, ElementNode[]>,
): void {
  for (const node of nodes) {
    nodeMap.set(node.id, node);

    if (isContainerNode(node) && node.children) {
      childrenByParentId.set(node.id, node.children);
      walkNodes(node.children, nodeMap, childrenByParentId);
    }
  }
}

/**
 * Builds flat lookup maps from a nested element tree.
 *
 * - `nodeMap`: every node by ID for O(1) lookup
 * - `childrenByParentId`: parent ID → children array for O(1) sibling lookup
 *
 * Root-level arrays (keyed by page ID) are included in `childrenByParentId`.
 */
export function buildMaps(tree: ElementTreeResponse): ElementMaps {
  const nodeMap = new Map<number, ElementNode>();
  const childrenByParentId = new Map<number, ElementNode[]>();

  for (const [parentKey, nodes] of Object.entries(tree)) {
    childrenByParentId.set(Number(parentKey), nodes);
    walkNodes(nodes, nodeMap, childrenByParentId);
  }

  return { nodeMap, childrenByParentId };
}

/**
 * React hook that memoizes element lookup maps from a tree response.
 */
export function useElementMaps(tree: ElementTreeResponse): ElementMaps {
  return useMemo(() => buildMaps(tree), [tree]);
}
