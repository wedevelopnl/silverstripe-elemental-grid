import type { EnrichedSectionNode } from '@/types/enriched';
import { getElementStatus } from '@/types/status';
import CollapseToggle from '@/components/CollapseToggle/CollapseToggle';
import RowBlock from '@/components/RowBlock/RowBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface SectionBlockProps {
  readonly section: EnrichedSectionNode;
}

export default function SectionBlock({ section }: SectionBlockProps) {
  const status = getElementStatus(section.statusFlags);
  const { isCollapsed, toggle } = section;

  const rootClasses = [
    'section-block',
    `section-block--${status}`,
    ...(isCollapsed ? ['section-block--collapsed'] : []),
  ].join(' ');

  return (
    <section className={rootClasses} data-testid="section-block">
      <div className="section-block__header">
        <CollapseToggle isCollapsed={isCollapsed} onToggle={toggle} label={section.title} />
        <h2 className="section-block__title">{section.title}</h2>
      </div>
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
    </section>
  );
}
