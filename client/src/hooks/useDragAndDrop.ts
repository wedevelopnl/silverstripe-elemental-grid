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
  ElementNode,
  ElementTreeResponse,
} from '@/types/elements';
import { useElementMaps } from '@/hooks/useElementMaps';
import { resolveReorderParams } from '@/utils/resolveReorderParams';

// --- Public types ---

export interface DragState {
  activeId: string;
  activeType: DraggableType;
  activeNode: ElementNode;
}

export interface UseDragAndDropOptions {
  tree: ElementTreeResponse;
  onReorder: (
    elementID: number,
    targetParentId: number,
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

// --- Hook ---

const POINTER_DISTANCE_THRESHOLD = 8;

export function useDragAndDrop({
  tree,
  onReorder,
}: UseDragAndDropOptions): UseDragAndDropReturn {
  const [dragState, setDragState] = useState<DragState | null>(null);
  const maps = useElementMaps(tree);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: POINTER_DISTANCE_THRESHOLD },
    }),
  );

  const handleDragStart = useCallback(
    (event: DragStartEvent) => {
      const parsed = parseDraggableId(String(event.active.id));
      if (!parsed) return;

      const node = maps.nodeMap.get(parsed.id);
      if (!node) return;

      setDragState({
        activeId: String(event.active.id),
        activeType: parsed.type,
        activeNode: node,
      });
    },
    [maps],
  );

  const handleDragEnd = useCallback(
    (event: DragEndEvent) => {
      setDragState(null);

      const { active, over } = event;
      if (!over || active.id === over.id) return;

      const activeParsed = parseDraggableId(String(active.id));
      const overParsed = parseDraggableId(String(over.id));
      if (!activeParsed || !overParsed) return;

      const activeNode = maps.nodeMap.get(activeParsed.id);
      if (!activeNode) return;

      const sourceParentId = activeNode.parentId;
      const sourceChildren = maps.childrenByParentId.get(sourceParentId);
      if (!sourceChildren) return;
      const sourceIndex = sourceChildren.findIndex((n) => n.id === activeParsed.id);

      let targetParentId: number;
      let containerChildren: ElementNode[];
      let insertIndex: number;

      if (overParsed.type === activeParsed.type) {
        // Over a sibling item — use the sibling's parentId
        const overNode = maps.nodeMap.get(overParsed.id);
        if (!overNode) return;

        targetParentId = overNode.parentId;
        const targetChildren = maps.childrenByParentId.get(targetParentId);
        if (!targetChildren) return;

        containerChildren = targetChildren;
        insertIndex = targetChildren.findIndex((n) => n.id === overParsed.id);
      } else {
        // Over a container — drop into it (container's own ID is the parent)
        const containerNode = maps.nodeMap.get(overParsed.id);
        if (!containerNode || !isContainerNode(containerNode)) {
          return;
        }

        targetParentId = containerNode.id;
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
        overContainerParentId: targetParentId,
        overIndex: filtered.indexOf(String(active.id)),
        containerItems: filtered,
        sourceContainerParentId: sourceParentId,
        sourceIndex,
      });

      if (params) {
        onReorder(params.elementID, params.targetParentId, params.afterElementID);
      }
    },
    [maps, onReorder],
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
