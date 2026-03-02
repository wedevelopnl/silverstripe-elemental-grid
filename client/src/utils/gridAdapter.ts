import { getAdapterConfig } from '@/api/config';
import type { AdapterConfig, ViewportConfig } from '@/types/adapter';

let cachedConfig: AdapterConfig | null = null;

function config(): AdapterConfig {
  if (cachedConfig === null) {
    cachedConfig = getAdapterConfig();
  }
  return cachedConfig;
}

export function getViewports(): readonly ViewportConfig[] {
  return config().viewports;
}

export function getDefaultViewport(): string {
  return config().defaultViewport;
}

export function getColumnCount(): number {
  return config().columnCount;
}

export function getRowClasses(): string {
  return config().rowClasses;
}

export function getWidthClass(width: number): string {
  return config().baseWidthClasses[String(width)] ?? '';
}

export function getOffsetClass(offset: number): string {
  return config().baseOffsetClasses[String(offset)] ?? '';
}
