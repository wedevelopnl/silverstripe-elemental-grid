import { renderHook, act } from '@testing-library/react';
import type { ReactNode } from 'react';

import { ViewportProvider, useViewportContext } from '@/hooks/ViewportContext';

vi.mock('@/utils/gridAdapter', () => ({
  getDefaultViewport: () => 'md',
}));

describe('ViewportContext', () => {
  it('throws when useViewportContext is used outside a ViewportProvider', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      renderHook(() => useViewportContext());
    }).toThrow('useViewportContext must be used within a ViewportProvider');

    spy.mockRestore();
  });

  it('provides activeViewport and setActiveViewport through the provider', () => {
    function wrapper({ children }: { children: ReactNode }) {
      return <ViewportProvider initialViewport="lg">{children}</ViewportProvider>;
    }

    const { result } = renderHook(() => useViewportContext(), { wrapper });

    expect(result.current.activeViewport).toBe('lg');
    expect(typeof result.current.setActiveViewport).toBe('function');
  });

  it('updates activeViewport when setActiveViewport is called', () => {
    function wrapper({ children }: { children: ReactNode }) {
      return <ViewportProvider initialViewport="md">{children}</ViewportProvider>;
    }

    const { result } = renderHook(() => useViewportContext(), { wrapper });

    act(() => {
      result.current.setActiveViewport('xl');
    });

    expect(result.current.activeViewport).toBe('xl');
  });

  it('defaults to getDefaultViewport when initialViewport is not provided', () => {
    function wrapper({ children }: { children: ReactNode }) {
      return <ViewportProvider>{children}</ViewportProvider>;
    }

    const { result } = renderHook(() => useViewportContext(), { wrapper });

    expect(result.current.activeViewport).toBe('md');
  });
});
