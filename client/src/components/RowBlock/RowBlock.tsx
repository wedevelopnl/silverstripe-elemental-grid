import type { RowNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import { useViewportContext } from '@/hooks/ViewportContext';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface RowBlockProps {
  readonly row: RowNode;
}

export default function RowBlock({ row }: RowBlockProps) {
  const { rowClasses } = useViewportContext();
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
            />
          ))
          : <EmptyState message="No columns" />}
      </div>
    </div>
  );
}
