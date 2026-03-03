import { DndContext, DragOverlay } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useElementTree } from '@/hooks/useElementTree';
import { useCollapseEnrichment } from '@/hooks/useCollapseEnrichment';
import { useDragAndDrop } from '@/hooks/useDragAndDrop';
import { useReorderElement } from '@/hooks/useElementMutations';
import { ViewportProvider } from '@/hooks/ViewportContext';
import { isSectionNode } from '@/types/elements';
import { buildDraggableId } from '@/types/dnd';
import { typedCollisionDetection } from '@/utils/collisionDetection';
import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import SectionBlock from '@/components/SectionBlock/SectionBlock';
import EmptyState from '@/components/EmptyState/EmptyState';
import DragOverlayContent from '@/components/DragOverlayContent/DragOverlayContent';

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

  const sections = data === undefined
    ? []
    : (data[String(areaId)] ?? []).filter(isSectionNode);

  const enrichedSections = useCollapseEnrichment(sections, areaId);

  const reorderMutation = useReorderElement(pageId ?? 0);

  const { sensors, dragState, handleDragStart, handleDragEnd, handleDragCancel } = useDragAndDrop({
    tree: data ?? {},
    areaId,
    onReorder: (elementID, targetAreaID, afterElementID) => {
      reorderMutation.mutate({
        params: { elementID, targetAreaID, afterElementID },
        tree: data ?? {},
      });
    },
  });

  const sectionIds = enrichedSections.map((s) => buildDraggableId('section', s.id));

  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      {isLoading && <p className="grid-editor__loading" data-testid="grid-editor-loading">Loading elements...</p>}
      {error !== null && (
        <p className="grid-editor__error">
          Failed to load elements: {error.message}
        </p>
      )}
      {data !== undefined && (
        <DndContext
          sensors={sensors}
          collisionDetection={typedCollisionDetection}
          onDragStart={handleDragStart}
          onDragEnd={handleDragEnd}
          onDragCancel={handleDragCancel}
        >
          <ViewportProvider>
            <ViewportSwitcher />
            <SortableContext items={sectionIds} strategy={verticalListSortingStrategy}>
              {enrichedSections.length > 0
                ? enrichedSections.map((section) => (
                  <SectionBlock key={section.id} section={section} />
                ))
                : <EmptyState message="No sections yet" variant="centered" />}
            </SortableContext>
          </ViewportProvider>
          <DragOverlay>
            {dragState !== null && (
              <DragOverlayContent node={dragState.activeNode} type={dragState.activeType} />
            )}
          </DragOverlay>
        </DndContext>
      )}
    </div>
  );
}
