import { useElementTree } from '@/hooks/useElementTree';
import { useViewport } from '@/hooks/useViewport';
import { isSectionNode } from '@/types/elements';
import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import SectionBlock from '@/components/SectionBlock/SectionBlock';
import EmptyState from '@/components/EmptyState/EmptyState';

interface GridEditorProps {
  readonly areaId: number;
  readonly pageId: number | null;
}

/**
 * Root component for the grid editor. Mounted by the entwine bridge
 * inside each `.grid-editor__container` element in the CMS.
 *
 * Composes ViewportSwitcher (viewport breakpoint selection) with
 * SectionBlock (section > row > column > element card hierarchy)
 * to render the full grid editing interface.
 */
export default function GridEditor({ areaId, pageId }: GridEditorProps) {
  const { data, isLoading, error } = useElementTree(pageId);
  const {
    viewports,
    activeViewport,
    setActiveViewport,
    columnCount,
    rowClasses,
    getWidthClass,
    getOffsetClass,
  } = useViewport();

  const sections = data === undefined
    ? []
    : Object.values(data).flat().filter(isSectionNode);

  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      {isLoading && <p className="grid-editor__loading">Loading elements...</p>}
      {error !== null && (
        <p className="grid-editor__error">
          Failed to load elements: {error.message}
        </p>
      )}
      {data !== undefined && (
        <>
          <ViewportSwitcher
            viewports={viewports}
            activeViewport={activeViewport}
            onViewportChange={setActiveViewport}
          />
          {sections.length > 0
            ? sections.map((section) => (
              <SectionBlock
                key={section.id}
                section={section}
                activeViewport={activeViewport}
                columnCount={columnCount}
                rowClasses={rowClasses}
                getWidthClass={getWidthClass}
                getOffsetClass={getOffsetClass}
              />
            ))
            : <EmptyState message="No sections yet" variant="centered" />}
        </>
      )}
    </div>
  );
}
