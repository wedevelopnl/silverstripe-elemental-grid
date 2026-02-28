import type { SectionNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';
import RowBlock from '@/components/RowBlock/RowBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface SectionBlockProps {
  readonly section: SectionNode;
}

export default function SectionBlock({ section }: SectionBlockProps) {
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
            />
          ))
          : <EmptyState message="No rows" />}
      </div>
    </div>
  );
}
