import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { getDefaultViewport } from '@/utils/gridAdapter';

export interface ViewportContextValue {
  readonly activeViewport: string;
  readonly setActiveViewport: (key: string) => void;
}

const ViewportContext = createContext<ViewportContextValue | null>(null);

interface ViewportProviderProps {
  readonly initialViewport?: string;
  readonly children: ReactNode;
}

export function ViewportProvider({ initialViewport, children }: ViewportProviderProps) {
  const [activeViewport, setActiveViewport] = useState(
    () => initialViewport ?? getDefaultViewport(),
  );

  const value: ViewportContextValue = useMemo(
    () => ({ activeViewport, setActiveViewport }),
    [activeViewport],
  );

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
