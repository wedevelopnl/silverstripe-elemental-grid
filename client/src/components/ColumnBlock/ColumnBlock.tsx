import type { ColumnNode, ViewportSettings } from '@/types/elements';
import { getElementStatus } from '@/types/status';
import { useViewportContext } from '@/hooks/ViewportContext';
import { useCollapse } from '@/hooks/useCollapse';
import { getColumnCount, getWidthClass, getOffsetClass } from '@/utils/gridAdapter';
import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';
import ElementCard from '@/components/ElementCard/ElementCard';
import EmptyState from '@/components/EmptyState/EmptyState';

interface ColumnBlockProps {
  readonly column: ColumnNode;
}

function resolveViewportSettings(
  column: ColumnNode,
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
  const { isCollapsed, toggle } = useCollapse(column.id);

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

  return (
    <div className={outerClasses.join(' ')}>
      <div className={innerClasses.join(' ')} data-testid="column-block">
        <div className="column-block__header">
          <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={column.title} />
          <span className="column-block__badge" data-testid="column-badge">
            {settings.visible ? `${settings.width}/${columnCount}` : 'hidden'}
          </span>
        </div>
        <div className="column-block__body">
          {column.children !== null && column.children.length > 0
            ? column.children.map((child) => (
              <ElementCard key={child.id} element={child} />
            ))
            : <EmptyState message="No content blocks" />}
        </div>
      </div>
    </div>
  );
}
