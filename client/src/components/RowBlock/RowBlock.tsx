import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, horizontalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { EnrichedRowNode } from '@/types/enriched';
import { getElementStatus } from '@/types/status';
import { buildDraggableId } from '@/types/dnd';
import { getRowClasses } from '@/utils/gridAdapter';
import DragHandle from '@/components/DragHandle/DragHandle';
import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface RowBlockProps {
  readonly row: EnrichedRowNode;
}

export default function RowBlock({ row }: RowBlockProps) {
  const rowClasses = getRowClasses();
  const status = getElementStatus(row.statusFlags);
  const { isCollapsed, toggle } = row;

  const sortableId = buildDraggableId('row', row.id);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable({ id: sortableId });

  const columnIds = (row.children ?? []).map((c) => buildDraggableId('column', c.id));

  const rootClasses = [
    'row-block',
    `row-block--${status}`,
    ...(isCollapsed ? ['row-block--collapsed'] : []),
    ...(isOver ? ['row-block--drop-target'] : []),
  ].join(' ');

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
    opacity: isDragging ? 0.3 : undefined,
  };

  return (
    <div ref={setNodeRef} style={style} className={rootClasses}>
      <div className="row-block__header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${row.title}`} />
        <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={row.title} />
        <h3 className="row-block__title">{row.title}</h3>
      </div>
      <div className={rowClasses}>
        <SortableContext items={columnIds} strategy={horizontalListSortingStrategy}>
          {row.children !== null && row.children.length > 0
            ? row.children.map((column) => (
              <ColumnBlock
                key={column.id}
                column={column}
              />
            ))
            : <EmptyState message="No columns" />}
        </SortableContext>
      </div>
    </div>
  );
}
