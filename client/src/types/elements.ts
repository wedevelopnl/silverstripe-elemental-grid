import { z } from 'zod';

// --- Container type constants ---

export const CONTAINER_TYPES = ['section', 'row', 'column'] as const;

export type ContainerType = (typeof CONTAINER_TYPES)[number];

// --- Shared schemas ---

export const blockSchemaSchema = z.object({
  typeName: z.string(),
  actions: z.object({
    edit: z.string(),
  }),
  content: z.string(),
});

const baseFieldsSchema = z.object({
  id: z.number().int(),
  title: z.string(),
  blockSchema: blockSchemaSchema,
  obsoleteClassName: z.string().nullable(),
  version: z.number().int(),
  isPublished: z.boolean(),
  isLiveVersion: z.boolean(),
  canDelete: z.boolean(),
  canPublish: z.boolean(),
  canUnpublish: z.boolean(),
  canCreate: z.boolean(),
  statusFlags: z.record(z.string(), z.unknown()),
  extensions: z.record(z.string(), z.unknown()).optional(),
});

// --- Leaf node schema (no containerType field) ---
// Passthrough allows extra keys from extension enrichers while still
// rejecting container nodes via the discriminated union ordering.

export const simpleElementNodeSchema = baseFieldsSchema.passthrough();

// --- Container node schemas (bottom-up: column → row → section) ---

export const columnNodeSchema = baseFieldsSchema.extend({
  containerType: z.literal('column'),
  allowedTypes: z.record(z.string(), z.string()).nullable(),
  children: z.array(simpleElementNodeSchema).nullable(),
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
export type BlockSchema = z.infer<typeof blockSchemaSchema>;

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
