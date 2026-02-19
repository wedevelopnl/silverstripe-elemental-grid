import type { SectionNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import RowBlock from '@/components/RowBlock/RowBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

import './SectionBlock.scss';

interface SectionBlockProps {
  readonly section: SectionNode;
  readonly activeViewport: string;
  readonly columnCount: number;
  readonly rowClasses: string;
  readonly getWidthClass: (width: number) => string;
  readonly getOffsetClass: (offset: number) => string;
}

export default function SectionBlock({
  section,
  activeViewport,
  columnCount,
  rowClasses,
  getWidthClass,
  getOffsetClass,
}: SectionBlockProps) {
  const status = deriveElementStatus(section.isPublished, section.isLiveVersion);

  return (
    <div className={`section-block section-block--${status}`}>
      <div className="section-block__title">{section.title}</div>
      <div className="section-block__body">
        {section.children !== null && section.children.length > 0
          ? section.children.map((row) => (
            <RowBlock
              key={row.id}
              row={row}
              activeViewport={activeViewport}
              columnCount={columnCount}
              rowClasses={rowClasses}
              getWidthClass={getWidthClass}
              getOffsetClass={getOffsetClass}
            />
          ))
          : <EmptyState message="No rows" />}
      </div>
    </div>
  );
}
