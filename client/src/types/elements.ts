import { z } from 'zod';

// --- Container type constants ---

export const CONTAINER_TYPES = ['section', 'row', 'column'] as const;

export type ContainerType = (typeof CONTAINER_TYPES)[number];

// --- Shared schemas ---

export const blockSchemaSchema = z.object({
  typeName: z.string(),
  label: z.string(),
  type: z.string(),
  title: z.string(),
  summary: z.string(),
});

const statusFlagValueSchema = z.object({
  text: z.string(),
  title: z.string(),
});

export const statusFlagsSchema = z.object({
  addedtodraft: statusFlagValueSchema.optional(),
  modified: statusFlagValueSchema.optional(),
  removedfromdraft: statusFlagValueSchema.optional(),
});

const baseFieldsSchema = z.object({
  id: z.number().int(),
  parentId: z.number().int().positive(),
  title: z.string().min(1),
  blockSchema: blockSchemaSchema,
  obsoleteClassName: z.string().nullable(),
  version: z.number().int(),
  canDelete: z.boolean(),
  canPublish: z.boolean(),
  canUnpublish: z.boolean(),
  canCreate: z.boolean(),
  statusFlags: statusFlagsSchema,
  extensions: z.record(z.string(), z.unknown()).optional(),
});

// --- Leaf node schema (no containerType field) ---
// Passthrough allows extra keys from extension enrichers while still
// rejecting container nodes via the discriminated union ordering.

export const simpleElementNodeSchema = baseFieldsSchema.passthrough();

// --- Grid settings schema (column-specific) ---

const viewportSettingsSchema = z.object({
  width: z.number().int(),
  offset: z.number().int(),
  visible: z.boolean(),
});

export const gridSettingsSchema = z.record(z.string(), viewportSettingsSchema);

// --- Container node schemas (bottom-up: column → row → section) ---

export const columnNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('column'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(simpleElementNodeSchema).nullable(),
  gridSettings: gridSettingsSchema,
});

export const rowNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('row'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(columnNodeSchema).nullable(),
});

export const sectionNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('section'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(rowNodeSchema).nullable(),
});

// --- Union schema ---
// Containers first: their literal containerType discriminates them before
// the simpler schema can match (simpleElementNodeSchema is a structural
// subset of any container schema).

export const elementNodeSchema = z.union([
  sectionNodeSchema,
  rowNodeSchema,
  columnNodeSchema,
  simpleElementNodeSchema,
]);

// --- Response schema ---

export const elementTreeResponseSchema = z.record(
  z.string(),
  z.array(elementNodeSchema),
);

// --- Inferred types ---

export type SimpleElementNode = z.infer<typeof simpleElementNodeSchema>;
export type ColumnNode = z.infer<typeof columnNodeSchema>;
export type RowNode = z.infer<typeof rowNodeSchema>;
export type SectionNode = z.infer<typeof sectionNodeSchema>;
export type ElementNode = z.infer<typeof elementNodeSchema>;
export type ContainerNode = SectionNode | RowNode | ColumnNode;
export type ElementTreeResponse = z.infer<typeof elementTreeResponseSchema>;
export type StatusFlags = z.infer<typeof statusFlagsSchema>;
export type BlockSchema = z.infer<typeof blockSchemaSchema>;
export type GridSettings = z.infer<typeof gridSettingsSchema>;
export type ViewportSettings = z.infer<typeof viewportSettingsSchema>;

// --- Type guards ---

export function isContainerNode(node: ElementNode): node is ContainerNode {
  return 'containerType' in node;
}

export function isSectionNode(node: ElementNode): node is SectionNode {
  return 'containerType' in node && node.containerType === 'section';
}

export function isRowNode(node: ElementNode): node is RowNode {
  return 'containerType' in node && node.containerType === 'row';
}

export function isColumnNode(node: ElementNode): node is ColumnNode {
  return 'containerType' in node && node.containerType === 'column';
}

export function isSimpleElementNode(
  node: ElementNode,
): node is SimpleElementNode {
  return !('containerType' in node);
}
