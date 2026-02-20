import type { SimpleElementNode } from '@/types/elements';
import { deriveElementStatus } from '@/types/status';

interface ElementCardProps {
  readonly element: SimpleElementNode;
}

/** Extracts the short class name from a fully-qualified PHP class name. */
function stripNamespace(typeName: string): string {
  const lastSeparator = typeName.lastIndexOf('\\');

  return lastSeparator === -1 ? typeName : typeName.substring(lastSeparator + 1);
}

/**
 * Compact read-only card showing an element's type, title, content preview,
 * and publication state via a colored left border.
 */
export default function ElementCard({ element }: ElementCardProps) {
  const status = deriveElementStatus(element.isPublished, element.isLiveVersion);
  const title = element.title || '(untitled)';
  const shortType = stripNamespace(element.blockSchema.typeName);
  const content = element.blockSchema.content;

  return (
    <div className={`element-card element-card--${status}`}>
      <div className="element-card__header">
        <span className="element-card__type">{shortType}</span>
        <span className="element-card__title">{title}</span>
      </div>
      <div className={`element-card__content${content === '' ? ' element-card__content--empty' : ''}`}>
        {content || 'No preview available'}
      </div>
    </div>
  );
}
