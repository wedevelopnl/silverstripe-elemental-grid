import { renderHook } from '@testing-library/react';
import type { ReactNode } from 'react';

import { ViewportProvider, useViewportContext } from '@/hooks/ViewportContext';
import type { ViewportContextValue } from '@/hooks/ViewportContext';

describe('ViewportContext', () => {
  it('throws when useViewportContext is used outside a ViewportProvider', () => {
    // Suppress console.error from the expected React error boundary output
    const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      renderHook(() => useViewportContext());
    }).toThrow('useViewportContext must be used within a ViewportProvider');

    spy.mockRestore();
  });

  it('provides context values through the provider', () => {
    const getWidthClass = (width: number) => `w-${width}`;
    const getOffsetClass = (offset: number) => `o-${offset}`;

    const contextValue: ViewportContextValue = {
      activeViewport: 'lg',
      columnCount: 16,
      rowClasses: 'columns is-multiline',
      getWidthClass,
      getOffsetClass,
    };

    function wrapper({ children }: { children: ReactNode }) {
      return <ViewportProvider value={contextValue}>{children}</ViewportProvider>;
    }

    const { result } = renderHook(() => useViewportContext(), { wrapper });

    expect(result.current.activeViewport).toBe('lg');
    expect(result.current.columnCount).toBe(16);
    expect(result.current.rowClasses).toBe('columns is-multiline');
    expect(result.current.getWidthClass).toBe(getWidthClass);
    expect(result.current.getOffsetClass).toBe(getOffsetClass);
  });
});
