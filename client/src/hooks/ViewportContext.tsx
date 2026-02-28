import { createContext, useContext } from 'react';
import type { ReactNode } from 'react';

export interface ViewportContextValue {
  readonly activeViewport: string;
  readonly columnCount: number;
  readonly rowClasses: string;
  readonly getWidthClass: (width: number) => string;
  readonly getOffsetClass: (offset: number) => string;
}

const ViewportContext = createContext<ViewportContextValue | null>(null);

interface ViewportProviderProps {
  readonly value: ViewportContextValue;
  readonly children: ReactNode;
}

export function ViewportProvider({ value, children }: ViewportProviderProps) {
  return (
    <ViewportContext.Provider value={value}>
      {children}
    </ViewportContext.Provider>
  );
}

export function useViewportContext(): ViewportContextValue {
  const value = useContext(ViewportContext);
  if (value === null) {
    throw new Error('useViewportContext must be used within a ViewportProvider');
  }
  return value;
}
