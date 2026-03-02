import { useCallback } from 'react';
import { useCollapseContext } from '@/hooks/CollapseContext';

export interface CollapseState {
  readonly isCollapsed: boolean;
  readonly toggle: () => void;
}

export function useCollapse(elementId: number): CollapseState {
  const { collapsedIds, toggle: contextToggle } = useCollapseContext();

  const isCollapsed = collapsedIds.has(elementId);

  const toggle = useCallback(
    () => { contextToggle(elementId); },
    [contextToggle, elementId],
  );

  return { isCollapsed, toggle };
}
