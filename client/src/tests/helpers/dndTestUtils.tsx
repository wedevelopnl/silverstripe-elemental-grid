import type { ReactNode } from 'react';
import { DndContext } from '@dnd-kit/core';
import { SortableContext } from '@dnd-kit/sortable';
import { ViewportProvider } from '@/hooks/ViewportContext';

/**
 * Creates a test wrapper that provides DndContext + SortableContext + ViewportProvider.
 * Components using useSortable need all of these ancestors.
 */
export function createDndWrapper(
  activeViewport = 'md',
  items: string[] = [],
): ({ children }: { children: ReactNode }) => ReactNode {
  return function DndWrapper({ children }: { children: ReactNode }) {
    return (
      <DndContext>
        <SortableContext items={items}>
          <ViewportProvider initialViewport={activeViewport}>
            {children}
          </ViewportProvider>
        </SortableContext>
      </DndContext>
    );
  };
}
