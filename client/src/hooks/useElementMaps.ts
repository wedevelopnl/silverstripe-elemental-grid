import { useMemo } from 'react';
import { isContainerNode } from '@/types/elements';
import type { ElementNode, ElementTreeResponse } from '@/types/elements';

export interface ElementMaps {
  nodeMap: Map<number, ElementNode>;
  childrenByAreaId: Map<number, ElementNode[]>;
}

/**
 * Walks the tree once, populating both lookup maps simultaneously.
 */
function walkNodes(
  nodes: ElementNode[],
  nodeMap: Map<number, ElementNode>,
  childrenByAreaId: Map<number, ElementNode[]>,
): void {
  for (const node of nodes) {
    nodeMap.set(node.id, node);

    if (isContainerNode(node) && node.children) {
      childrenByAreaId.set(node.childAreaId, node.children);
      walkNodes(node.children, nodeMap, childrenByAreaId);
    }
  }
}

/**
 * Builds flat lookup maps from a nested element tree.
 *
 * - `nodeMap`: every node by ID for O(1) lookup
 * - `childrenByAreaId`: area ID → children array for O(1) sibling lookup
 *
 * Root-level area arrays are included in `childrenByAreaId`.
 */
export function buildMaps(tree: ElementTreeResponse): ElementMaps {
  const nodeMap = new Map<number, ElementNode>();
  const childrenByAreaId = new Map<number, ElementNode[]>();

  for (const [areaKey, nodes] of Object.entries(tree)) {
    childrenByAreaId.set(Number(areaKey), nodes);
    walkNodes(nodes, nodeMap, childrenByAreaId);
  }

  return { nodeMap, childrenByAreaId };
}

/**
 * React hook that memoizes element lookup maps from a tree response.
 */
export function useElementMaps(tree: ElementTreeResponse): ElementMaps {
  return useMemo(() => buildMaps(tree), [tree]);
}
