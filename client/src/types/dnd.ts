import type { ElementNode } from './elements';
import { isContainerNode } from './elements';

export const DRAGGABLE_TYPES = ['section', 'row', 'column', 'element'] as const;

export type DraggableType = (typeof DRAGGABLE_TYPES)[number];

export interface ParsedDraggableId {
  readonly type: DraggableType;
  readonly id: number;
}

const SEPARATOR = '-';

function isDraggableType(value: string): value is DraggableType {
  return (DRAGGABLE_TYPES as readonly string[]).includes(value);
}

export function buildDraggableId(type: DraggableType, id: number): string {
  return `${type}${SEPARATOR}${id}`;
}

export function parseDraggableId(compositeId: string): ParsedDraggableId | null {
  const separatorIndex = compositeId.indexOf(SEPARATOR);
  if (separatorIndex <= 0) return null;

  const type = compositeId.slice(0, separatorIndex);
  if (!isDraggableType(type)) return null;

  const numericId = Number(compositeId.slice(separatorIndex + 1));
  if (!Number.isInteger(numericId) || numericId <= 0) return null;

  return { type, id: numericId };
}

export function getDraggableType(compositeId: string): DraggableType | null {
  return parseDraggableId(compositeId)?.type ?? null;
}

/**
 * Derives the draggable type from a node's shape: container nodes use their
 * containerType, leaf nodes are always 'element'.
 */
export function getDraggableTypeForNode(node: ElementNode): DraggableType {
  if (!isContainerNode(node)) return 'element';
  return node.containerType;
}

/**
 * Maps a draggable type to the container type that holds its siblings.
 * Sections live in the root area, rows in sections, columns in rows, elements in columns.
 */
export const PARENT_CONTAINER_TYPE: Record<DraggableType, DraggableType | 'root'> = {
  section: 'root',
  row: 'section',
  column: 'row',
  element: 'column',
};
