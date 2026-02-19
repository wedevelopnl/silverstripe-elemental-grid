import type { RowNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

import './RowBlock.scss';

interface RowBlockProps {
  readonly row: RowNode;
  readonly activeViewport: string;
  readonly columnCount: number;
  readonly rowClasses: string;
  readonly getWidthClass: (width: number) => string;
  readonly getOffsetClass: (offset: number) => string;
}

export default function RowBlock({
  row,
  activeViewport,
  columnCount,
  rowClasses,
  getWidthClass,
  getOffsetClass,
}: RowBlockProps) {
  const status = deriveElementStatus(row.isPublished, row.isLiveVersion);

  return (
    <div className={`row-block row-block--${status}`}>
      <div className="row-block__title">{row.title}</div>
      <div className={rowClasses}>
        {row.children !== null && row.children.length > 0
          ? row.children.map((column) => (
            <ColumnBlock
              key={column.id}
              column={column}
              activeViewport={activeViewport}
              columnCount={columnCount}
              getWidthClass={getWidthClass}
              getOffsetClass={getOffsetClass}
            />
          ))
          : <EmptyState message="No columns" />}
      </div>
    </div>
  );
}
