import { useElementTree } from '@/hooks/useElementTree';
import type { ElementNode } from '@/types/elements';
import { isContainerNode } from '@/types/elements';

interface GridEditorProps {
  readonly areaId: number;
  readonly pageId: number | null;
}

function ElementSummary({ node }: { readonly node: ElementNode }) {
  const type = isContainerNode(node)
    ? node.containerType
    : node.blockSchema.typeName;

  return (
    <li data-element-id={node.id}>
      <strong>{node.title || '(untitled)'}</strong>{' '}
      <span className="grid-editor__type-badge">[{type}]</span>
      {isContainerNode(node) && node.children !== null && (
        <ul>
          {node.children.map((child) => (
            <ElementSummary key={child.id} node={child} />
          ))}
        </ul>
      )}
    </li>
  );
}

/**
 * Root component for the grid editor. Mounted by the entwine bridge
 * inside each `.grid-editor__container` element in the CMS.
 *
 * Renders the element tree from the backend API using TanStack Query.
 * Currently shows a proof-of-life tree view — will be expanded to
 * render the full drag-and-drop grid editing interface.
 */
export default function GridEditor({ areaId, pageId }: GridEditorProps) {
  const { data, isLoading, error } = useElementTree(pageId);

  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      {isLoading && <p className="grid-editor__loading">Loading elements...</p>}
      {error !== null && (
        <p className="grid-editor__error">
          Failed to load elements: {error.message}
        </p>
      )}
      {data !== undefined && (
        <div className="grid-editor__tree">
          {Object.entries(data).map(([relationName, nodes]) => (
            <div key={relationName} className="grid-editor__relation">
              <h3 className="grid-editor__relation-name">{relationName}</h3>
              <ul>
                {nodes.map((node) => (
                  <ElementSummary key={node.id} node={node} />
                ))}
              </ul>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
