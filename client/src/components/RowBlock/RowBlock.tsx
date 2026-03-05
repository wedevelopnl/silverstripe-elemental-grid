import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, horizontalListSortingStrategy } from '@dnd-kit/sortable';
import type { EnrichedRowNode } from '@/types/enriched';
import { getElementStatus } from '@/types/status';
import { useDragContext } from '@/hooks/useDragAndDrop';
import { buildSortableStyle } from '@/utils/sortableStyles';
import { buildBlockClasses } from '@/utils/blockClasses';
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
  const { activeType } = useDragContext();

  const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable({ id: row.sortableId });

  const showDropTarget = isOver && activeType === 'row';

  const rootClasses = buildBlockClasses('row-block', status, {
    collapsed: isCollapsed,
    'drop-target': showDropTarget,
  });

  const style = buildSortableStyle(transform, transition, isDragging);

  return (
    <div ref={setNodeRef} style={style} className={rootClasses} data-testid="row-block">
      <div className="row-block__header" data-testid="row-header">
        <DragHandle listeners={listeners} attributes={attributes} label={`Move ${row.title}`} />
        <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={row.title} />
        <h3 className="row-block__title" data-testid="row-title">{row.title}</h3>
      </div>
      <div className={rowClasses}>
        <SortableContext items={row.childSortableIds} strategy={horizontalListSortingStrategy}>
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
