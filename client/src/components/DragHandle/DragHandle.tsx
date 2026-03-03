import type { DraggableAttributes, DraggableSyntheticListeners } from '@dnd-kit/core';

import './DragHandle.scss';

interface DragHandleProps {
  readonly listeners: DraggableSyntheticListeners;
  readonly attributes: DraggableAttributes;
  readonly label?: string;
}

export default function DragHandle({
  listeners,
  attributes,
  label = 'Drag to reorder',
}: DragHandleProps): React.JSX.Element {
  return (
    <button
      type="button"
      className="drag-handle"
      data-testid="drag-handle"
      aria-label={label}
      {...listeners}
      {...attributes}
    >
      <span className="drag-handle__icon" aria-hidden="true" />
    </button>
  );
}
