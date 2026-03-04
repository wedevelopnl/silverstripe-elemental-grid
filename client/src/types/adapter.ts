import { z } from 'zod';

const viewportConfigSchema = z.object({
  key: z.string(),
  label: z.string(),
});

export const adapterConfigSchema = z.object({
  viewports: z.array(viewportConfigSchema),
  defaultViewport: z.string(),
  columnCount: z.number().int().positive(),
  rowClasses: z.string(),
  baseWidthClasses: z.record(z.string(), z.string()),
  baseOffsetClasses: z.record(z.string(), z.string()),
});

export type ViewportConfig = z.infer<typeof viewportConfigSchema>;
export type AdapterConfig = z.infer<typeof adapterConfigSchema>;
