import type { ReactNode } from 'react';
import { DndContext } from '@dnd-kit/core';
import { SortableContext } from '@dnd-kit/sortable';
import { DragContext } from '@/hooks/useDragAndDrop';
import type { DraggableType } from '@/types/dnd';
import { ViewportProvider } from '@/hooks/ViewportContext';

interface DndWrapperOptions {
  activeViewport?: string;
  items?: string[];
  activeType?: DraggableType | null;
}

/**
 * Creates a test wrapper that provides DndContext + DragContext + SortableContext + ViewportProvider.
 * Components using useSortable need all of these ancestors.
 */
export function createDndWrapper(
  activeViewport = 'md',
  items: string[] = [],
  activeType: DraggableType | null = null,
): ({ children }: { children: ReactNode }) => ReactNode {
  return function DndWrapper({ children }: { children: ReactNode }) {
    return (
      <DndContext>
        <DragContext.Provider value={{ activeType }}>
          <SortableContext items={items}>
            <ViewportProvider initialViewport={activeViewport}>
              {children}
            </ViewportProvider>
          </SortableContext>
        </DragContext.Provider>
      </DndContext>
    );
  };
}
