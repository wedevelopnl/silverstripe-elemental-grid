import { useState, useMemo } from 'react';
import { getAdapterConfig } from '@/api/config';
import type { AdapterConfig, ViewportConfig } from '@/types/adapter';

export interface UseViewportReturn {
  readonly viewports: readonly ViewportConfig[];
  readonly activeViewport: string;
  readonly setActiveViewport: (key: string) => void;
  readonly columnCount: number;
  readonly rowClasses: string;
  readonly getWidthClass: (width: number) => string;
  readonly getOffsetClass: (offset: number) => string;
}

export function useViewport(): UseViewportReturn {
  const config: AdapterConfig = useMemo(() => getAdapterConfig(), []);
  const [activeViewport, setActiveViewport] = useState(config.defaultViewport);

  const getWidthClass = useMemo(
    () => (width: number) => config.baseWidthClasses[String(width)] ?? '',
    [config.baseWidthClasses],
  );

  const getOffsetClass = useMemo(
    () => (offset: number) => config.baseOffsetClasses[String(offset)] ?? '',
    [config.baseOffsetClasses],
  );

  return {
    viewports: config.viewports,
    activeViewport,
    setActiveViewport,
    columnCount: config.columnCount,
    rowClasses: config.rowClasses,
    getWidthClass,
    getOffsetClass,
  };
}
