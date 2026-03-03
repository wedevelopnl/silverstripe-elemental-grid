import type { ElementNode } from '@/types/elements';
import { isContainerNode } from '@/types/elements';
import type { DraggableType } from '@/types/dnd';

import './DragOverlayContent.scss';

interface DragOverlayContentProps {
  readonly node: ElementNode;
  readonly type: DraggableType;
}

function pluralize(count: number, singular: string): string {
  return count === 1 ? `${count} ${singular}` : `${count} ${singular}s`;
}

function getChildCount(node: ElementNode): number {
  if (!isContainerNode(node)) return 0;
  return node.children?.length ?? 0;
}

function SectionPreview({ node }: { readonly node: ElementNode }): React.JSX.Element {
  return (
    <>
      <span className="drag-overlay-content__title">{node.title}</span>
      <span className="drag-overlay-content__meta">{pluralize(getChildCount(node), 'row')}</span>
    </>
  );
}

function RowPreview({ node }: { readonly node: ElementNode }): React.JSX.Element {
  return (
    <>
      <span className="drag-overlay-content__title">{node.title}</span>
      <span className="drag-overlay-content__meta">{pluralize(getChildCount(node), 'column')}</span>
    </>
  );
}

function ColumnPreview({ node }: { readonly node: ElementNode }): React.JSX.Element {
  return (
    <span className="drag-overlay-content__title">{node.title}</span>
  );
}

function ElementPreview({ node }: { readonly node: ElementNode }): React.JSX.Element {
  return (
    <>
      <span className="drag-overlay-content__type">{node.blockSchema.label}</span>
      <span className="drag-overlay-content__title">{node.title}</span>
    </>
  );
}

const PREVIEW_BY_TYPE: Record<DraggableType, React.ComponentType<{ readonly node: ElementNode }>> = {
  section: SectionPreview,
  row: RowPreview,
  column: ColumnPreview,
  element: ElementPreview,
};

export default function DragOverlayContent({
  node,
  type,
}: DragOverlayContentProps): React.JSX.Element {
  const Preview = PREVIEW_BY_TYPE[type];

  return (
    <div className={`drag-overlay-content drag-overlay-content--${type}`}>
      <Preview node={node} />
    </div>
  );
}
