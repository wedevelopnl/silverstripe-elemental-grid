import { createContext, useCallback, useContext, useState } from 'react';
import {
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import type {
  DragCancelEvent,
  DragEndEvent,
  DragStartEvent,
  SensorDescriptor,
  SensorOptions,
} from '@dnd-kit/core';
import {
  buildDraggableId,
  parseDraggableId,
} from '@/types/dnd';
import type { DraggableType } from '@/types/dnd';
import { isContainerNode } from '@/types/elements';
import type {
  ContainerNode,
  ElementNode,
  ElementTreeResponse,
} from '@/types/elements';
import { resolveReorderParams } from '@/utils/resolveReorderParams';

// --- Public types ---

export interface DragState {
  activeId: string;
  activeType: DraggableType;
  activeNode: ElementNode;
}

export interface UseDragAndDropOptions {
  tree: ElementTreeResponse;
  areaId: number;
  onReorder: (
    elementID: number,
    targetAreaID: number,
    afterElementID: number | null,
  ) => void;
}

export interface UseDragAndDropReturn {
  sensors: SensorDescriptor<SensorOptions>[];
  dragState: DragState | null;
  handleDragStart: (event: DragStartEvent) => void;
  handleDragEnd: (event: DragEndEvent) => void;
  handleDragCancel: (event: DragCancelEvent) => void;
}

// --- Drag context ---

export interface DragContextValue {
  activeType: DraggableType | null;
}

export const DragContext = createContext<DragContextValue>({ activeType: null });

export function useDragContext(): DragContextValue {
  return useContext(DragContext);
}

// --- Tree search helpers ---

interface ContainerInfo {
  areaId: number;
  items: ElementNode[];
  index: number;
}

/**
 * Recursively searches the tree for a node by its numeric ID.
 * Checks all root-level area arrays and recurses into container children.
 */
export function findNodeById(
  tree: ElementTreeResponse,
  id: number,
): ElementNode | null {
  for (const nodes of Object.values(tree)) {
    for (const node of nodes) {
      if (node.id === id) return node;

      if (isContainerNode(node)) {
        const found = findNodeInContainer(node, id);
        if (found) return found;
      }
    }
  }

  return null;
}

function findNodeInContainer(
  container: ContainerNode,
  id: number,
): ElementNode | null {
  if (!container.children) return null;

  for (const child of container.children) {
    if (child.id === id) return child;

    if (isContainerNode(child)) {
      const found = findNodeInContainer(child, id);
      if (found) return found;
    }
  }

  return null;
}

/**
 * Finds the container (by childAreaId) that directly holds a given node,
 * along with the sibling list and the node's index within it.
 *
 * Root-level tree entries use the numeric area key as the container's areaId.
 * Nested nodes use their parent container's childAreaId.
 */
export function findContainerForNode(
  tree: ElementTreeResponse,
  nodeId: number,
  _rootAreaId: number,
): ContainerInfo | null {
  for (const [areaKey, nodes] of Object.entries(tree)) {
    const index = nodes.findIndex((n) => n.id === nodeId);
    if (index !== -1) {
      return { areaId: Number(areaKey), items: nodes, index };
    }

    for (const node of nodes) {
      if (isContainerNode(node)) {
        const result = findInContainerChildren(node, nodeId);
        if (result) return result;
      }
    }
  }

  return null;
}

function findInContainerChildren(
  container: ContainerNode,
  nodeId: number,
): ContainerInfo | null {
  if (container.childAreaId === null || !container.children) return null;

  const index = container.children.findIndex((n) => n.id === nodeId);
  if (index !== -1) {
    return {
      areaId: container.childAreaId,
      items: container.children,
      index,
    };
  }

  for (const child of container.children) {
    if (isContainerNode(child)) {
      const result = findInContainerChildren(child, nodeId);
      if (result) return result;
    }
  }

  return null;
}

// --- Hook ---

const POINTER_DISTANCE_THRESHOLD = 8;

export function useDragAndDrop({
  tree,
  areaId,
  onReorder,
}: UseDragAndDropOptions): UseDragAndDropReturn {
  const [dragState, setDragState] = useState<DragState | null>(null);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: POINTER_DISTANCE_THRESHOLD },
    }),
  );

  const handleDragStart = useCallback(
    (event: DragStartEvent) => {
      const parsed = parseDraggableId(String(event.active.id));
      if (!parsed) return;

      const node = findNodeById(tree, parsed.id);
      if (!node) return;

      setDragState({
        activeId: String(event.active.id),
        activeType: parsed.type,
        activeNode: node,
      });
    },
    [tree],
  );

  const handleDragEnd = useCallback(
    (event: DragEndEvent) => {
      setDragState(null);

      const { active, over } = event;
      if (!over || active.id === over.id) return;

      const activeParsed = parseDraggableId(String(active.id));
      const overParsed = parseDraggableId(String(over.id));
      if (!activeParsed || !overParsed) return;

      const sourceInfo = findContainerForNode(tree, activeParsed.id, areaId);
      if (!sourceInfo) return;

      let targetAreaId: number;
      let containerChildren: ElementNode[];
      let insertIndex: number;

      if (overParsed.type === activeParsed.type) {
        // Over a sibling item — find the sibling's container
        const targetInfo = findContainerForNode(tree, overParsed.id, areaId);
        if (!targetInfo) return;

        targetAreaId = targetInfo.areaId;
        containerChildren = targetInfo.items;
        insertIndex = targetInfo.items.findIndex((n) => n.id === overParsed.id);
      } else {
        // Over a container — drop into it
        const containerNode = findNodeById(tree, overParsed.id);
        if (
          !containerNode ||
          !isContainerNode(containerNode) ||
          containerNode.childAreaId === null
        ) {
          return;
        }

        targetAreaId = containerNode.childAreaId;
        containerChildren = containerNode.children ?? [];
        insertIndex = containerChildren.length;
      }

      // Build the ordered ID list with the active item placed at the target position
      const compositeIds = containerChildren.map((n) =>
        buildDraggableId(activeParsed.type, n.id),
      );
      const filtered = compositeIds.filter((id) => id !== String(active.id));
      const clampedIndex = Math.min(insertIndex, filtered.length);
      filtered.splice(clampedIndex, 0, String(active.id));

      const params = resolveReorderParams({
        activeId: String(active.id),
        overContainerAreaId: targetAreaId,
        overIndex: filtered.indexOf(String(active.id)),
        containerItems: filtered,
        sourceContainerAreaId: sourceInfo.areaId,
        sourceIndex: sourceInfo.index,
      });

      if (params) {
        onReorder(params.elementID, params.targetAreaID, params.afterElementID);
      }
    },
    [tree, areaId, onReorder],
  );

  const handleDragCancel = useCallback(() => {
    setDragState(null);
  }, []);

  return {
    sensors,
    dragState,
    handleDragStart,
    handleDragEnd,
    handleDragCancel,
  };
}
