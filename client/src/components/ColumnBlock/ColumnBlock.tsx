import type { ColumnNode, ViewportSettings } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import { useViewportContext } from '@/hooks/ViewportContext';
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
  const { activeViewport, columnCount, getWidthClass, getOffsetClass } = useViewportContext();
  const settings = resolveViewportSettings(column, activeViewport, columnCount);
  const status = deriveElementStatus(column.isPublished, column.isLiveVersion);

  const outerClasses = [getWidthClass(settings.width)];
  if (settings.offset > 0) {
    outerClasses.push(getOffsetClass(settings.offset));
  }

  const innerClasses = ['column-block', `column-block--${status}`];
  if (!settings.visible) {
    innerClasses.push('column-block--hidden');
  }

  return (
    <div className={outerClasses.join(' ')}>
      <div className={innerClasses.join(' ')} data-testid="column-block">
        <div className="column-block__header">
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
