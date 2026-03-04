import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { SimpleElementNode } from '@/types/elements';
import { getElementStatus } from '@/types/status';
import { buildDraggableId } from '@/types/dnd';
import DragHandle from '@/components/DragHandle/DragHandle';

interface ElementCardProps {
  readonly element: SimpleElementNode;
}

/**
 * Compact read-only card showing an element's type, title, content preview,
 * and publication state via a colored left border.
 */
export default function ElementCard({ element }: ElementCardProps) {
  const sortableId = buildDraggableId('element', element.id);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: sortableId });
  const status = getElementStatus(element.statusFlags);
  const label = element.blockSchema.label;
  const content = element.blockSchema.content;

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
    opacity: isDragging ? 0.3 : undefined,
  };

  return (
    <div ref={setNodeRef} style={style} className={`element-card element-card--${status}`} data-testid="element-card">
      <div className="element-card__header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${element.title}`} />
        <span className="element-card__type">{label}</span>
        <h4 className="element-card__title" data-testid="element-card-title">{element.title}</h4>
      </div>
      <div className={`element-card__content${content === '' ? ' element-card__content--empty' : ''}`}>
        {content || 'No preview available'}
      </div>
    </div>
  );
}
