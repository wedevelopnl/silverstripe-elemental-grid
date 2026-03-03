import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { ViewportSettings } from '@/types/elements';
import type { EnrichedColumnNode } from '@/types/enriched';
import { getElementStatus } from '@/types/status';
import { buildDraggableId } from '@/types/dnd';
import { useViewportContext } from '@/hooks/ViewportContext';
import { getColumnCount, getWidthClass, getOffsetClass } from '@/utils/gridAdapter';
import DragHandle from '@/components/DragHandle/DragHandle';
import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';
import ElementCard from '@/components/ElementCard/ElementCard';
import EmptyState from '@/components/EmptyState/EmptyState';

interface ColumnBlockProps {
  readonly column: EnrichedColumnNode;
}

function resolveViewportSettings(
  column: EnrichedColumnNode,
  activeViewport: string,
  columnCount: number,
): ViewportSettings {
  return column.gridSettings[activeViewport] ?? {
    width: columnCount,
    offset: 0,
    visible: true,
  };
}

export default function ColumnBlock({ column }: ColumnBlockProps) {
  const { activeViewport } = useViewportContext();
  const columnCount = getColumnCount();
  const settings = resolveViewportSettings(column, activeViewport, columnCount);
  const status = getElementStatus(column.statusFlags);
  const { isCollapsed, toggle } = column;

  const sortableId = buildDraggableId('column', column.id);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable({ id: sortableId });

  const elementIds = (column.children ?? []).map((c) => buildDraggableId('element', c.id));

  const outerClasses = [getWidthClass(settings.width)];
  if (settings.offset > 0) {
    outerClasses.push(getOffsetClass(settings.offset));
  }

  const innerClasses = ['column-block', `column-block--${status}`];
  if (!settings.visible) {
    innerClasses.push('column-block--hidden');
  }
  if (isCollapsed) {
    innerClasses.push('column-block--collapsed');
  }
  if (isOver) {
    innerClasses.push('column-block--drop-target');
  }

  const sortableStyle: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
    opacity: isDragging ? 0.3 : undefined,
  };

  return (
    <div ref={setNodeRef} style={sortableStyle} className={outerClasses.join(' ')}>
      <div className={innerClasses.join(' ')} data-testid="column-block">
        <div className="column-block__header">
          <DragHandle listeners={listeners} attributes={attributes} label={`Move ${column.title}`} />
          <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={column.title} />
          <span className="column-block__badge" data-testid="column-badge">
            {settings.visible ? `${settings.width}/${columnCount}` : 'hidden'}
          </span>
        </div>
        <div className="column-block__body">
          <SortableContext items={elementIds} strategy={verticalListSortingStrategy}>
            {column.children !== null && column.children.length > 0
              ? column.children.map((child) => (
                <ElementCard key={child.id} element={child} />
              ))
              : <EmptyState message="No content blocks" />}
          </SortableContext>
        </div>
      </div>
    </div>
  );
}
