import type { ReactNode } from 'react';
import { ViewportProvider } from '@/hooks/ViewportContext';
import type { ViewportContextValue } from '@/hooks/ViewportContext';

const defaultViewportContext: ViewportContextValue = {
  activeViewport: 'md',
  columnCount: 12,
  rowClasses: 'row',
  getWidthClass: (width: number) => `col-${width}`,
  getOffsetClass: (offset: number) => `offset-${offset}`,
};

export function createViewportWrapper(
  overrides?: Partial<ViewportContextValue>,
): ({ children }: { children: ReactNode }) => ReactNode {
  const value = { ...defaultViewportContext, ...overrides };

  return function ViewportWrapper({ children }: { children: ReactNode }) {
    return <ViewportProvider value={value}>{children}</ViewportProvider>;
  };
}
