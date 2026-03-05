import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import type { ViewportSettings } from '@/types/elements';
import type { EnrichedColumnNode } from '@/types/enriched';
import { getElementStatus } from '@/types/status';
import { useDragContext } from '@/hooks/useDragAndDrop';
import { buildSortableStyle } from '@/utils/sortableStyles';
import { buildBlockClasses } from '@/utils/blockClasses';
import { useViewportContext } from '@/hooks/ViewportContext';
import { getColumnCount, getWidthClass, getOffsetClass } from '@/utils/gridAdapter';
import DragHandle from '@/components/DragHandle/DragHandle';
import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';
import ElementCard from '@/components/ElementCard/ElementCard';
import EmptyState from '@/components/EmptyState/EmptyState';

interface ColumnBlockProps {
  readonly column: EnrichedColumnNode;
}

const VIEWPORT_HIDDEN_LABEL = 'hidden';

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
  const { activeType } = useDragContext();

  const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable({ id: column.sortableId });

  const outerClasses = [getWidthClass(settings.width)];
  if (settings.offset > 0) {
    outerClasses.push(getOffsetClass(settings.offset));
  }

  const showDropTarget = isOver && activeType === 'column';

  const innerClasses = buildBlockClasses('column-block', status, {
    hidden: !settings.visible,
    collapsed: isCollapsed,
    'drop-target': showDropTarget,
  });

  const sortableStyle = buildSortableStyle(transform, transition, isDragging);

  return (
    <div ref={setNodeRef} style={sortableStyle} className={outerClasses.join(' ')}>
      <div className={innerClasses} data-testid="column-block">
        <div className="column-block__header" data-testid="column-header">
          <DragHandle listeners={listeners} attributes={attributes} label={`Move ${column.title}`} />
          <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={column.title} />
          <span className="column-block__badge" data-testid="column-badge">
            {settings.visible ? `${settings.width}/${columnCount}` : VIEWPORT_HIDDEN_LABEL}
          </span>
        </div>
        <div className="column-block__body">
          <SortableContext items={column.childSortableIds} strategy={verticalListSortingStrategy}>
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
