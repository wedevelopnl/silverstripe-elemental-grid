import type { RowNode } from '@/types/elements';
import { getElementStatus } from '@/types/status';
import { useViewportContext } from '@/hooks/ViewportContext';
import ColumnBlock from '@/components/ColumnBlock/ColumnBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface RowBlockProps {
  readonly row: RowNode;
}

export default function RowBlock({ row }: RowBlockProps) {
  const { rowClasses } = useViewportContext();
  const status = getElementStatus(row.statusFlags);

  return (
    <div className={`row-block row-block--${status}`}>
      <h3 className="row-block__title">{row.title}</h3>
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
