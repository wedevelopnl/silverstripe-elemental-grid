import { CSS } from '@dnd-kit/utilities';
import type { Transform } from '@dnd-kit/utilities';

const DRAGGING_OPACITY = 0.3;

export function buildSortableStyle(
  transform: Transform | null,
  transition: string | undefined,
  isDragging: boolean,
): React.CSSProperties {
  return {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
    opacity: isDragging ? DRAGGING_OPACITY : undefined,
  };
}
