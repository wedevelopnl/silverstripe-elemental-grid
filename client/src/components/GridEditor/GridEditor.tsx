import { useMemo } from 'react';
import { DndContext, DragOverlay } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useElementTree } from '@/hooks/useElementTree';
import { useTreeEnrichment } from '@/hooks/useTreeEnrichment';
import { useDragAndDrop, DragContext } from '@/hooks/useDragAndDrop';
import { useReorderElement } from '@/hooks/useElementMutations';
import { ViewportProvider } from '@/hooks/ViewportContext';
import { isSectionNode } from '@/types/elements';
import { typedCollisionDetection } from '@/utils/collisionDetection';
import ViewportSwitcher from '@/components/ViewportSwitcher/ViewportSwitcher';
import SectionBlock from '@/components/SectionBlock/SectionBlock';
import EmptyState from '@/components/EmptyState/EmptyState';
import DragOverlayContent from '@/components/DragOverlayContent/DragOverlayContent';

interface GridEditorProps {
  readonly pageId: number | null;
  readonly zone: string;
}

/**
 * Root component for the grid editor. Mounted by the entwine bridge
 * inside each `.grid-editor__container` element in the CMS.
 *
 * Composes ViewportSwitcher (viewport breakpoint selection) with
 * SectionBlock (section > row > column > element card hierarchy)
 * to render the full grid editing interface.
 */
export default function GridEditor({ pageId, zone }: GridEditorProps) {
  const { data, isLoading, error } = useElementTree(pageId, zone);

  const sections = data === undefined
    ? []
    : (data[String(pageId)] ?? []).filter(isSectionNode);

  const enrichedSections = useTreeEnrichment(sections, pageId ?? 0);

  const reorderMutation = useReorderElement(pageId ?? 0, zone);

  const { sensors, dragState, handleDragStart, handleDragEnd, handleDragCancel } = useDragAndDrop({
    tree: data ?? {},
    onReorder: (elementID, targetParentId, afterElementID) => {
      reorderMutation.mutate({
        params: { elementID, targetParentId, afterElementID },
        tree: data ?? {},
      });
    },
  });

  const sectionIds = enrichedSections.map((s) => s.sortableId);

  const dragContextValue = useMemo(
    () => ({ activeType: dragState?.activeType ?? null }),
    [dragState?.activeType],
  );

  return (
    <div className="grid-editor" data-page-id={pageId ?? undefined} data-zone={zone} data-testid="grid-editor">
      {isLoading && <p className="grid-editor__loading" data-testid="grid-editor-loading">Loading elements...</p>}
      {error !== null && (
        <p className="grid-editor__error">
          Failed to load elements: {error.message}
        </p>
      )}
      {data !== undefined && (
        <ViewportProvider>
          <ViewportSwitcher />
          <DndContext
            sensors={sensors}
            collisionDetection={typedCollisionDetection}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
            onDragCancel={handleDragCancel}
          >
            <DragContext.Provider value={dragContextValue}>
              <SortableContext items={sectionIds} strategy={verticalListSortingStrategy}>
                {enrichedSections.length > 0
                  ? enrichedSections.map((section) => (
                    <SectionBlock key={section.id} section={section} />
                  ))
                  : <EmptyState message="No sections yet" variant="centered" />}
              </SortableContext>
            </DragContext.Provider>
            <DragOverlay>
              {dragState !== null && (
                <DragOverlayContent node={dragState.activeNode} type={dragState.activeType} />
              )}
            </DragOverlay>
          </DndContext>
        </ViewportProvider>
      )}
    </div>
  );
}
