import type { ReactNode } from 'react';
import { ViewportProvider } from '@/hooks/ViewportContext';

export function createViewportWrapper(
  activeViewport = 'md',
): ({ children }: { children: ReactNode }) => ReactNode {
  return function ViewportWrapper({ children }: { children: ReactNode }) {
    return (
      <ViewportProvider initialViewport={activeViewport}>
        {children}
      </ViewportProvider>
    );
  };
}
