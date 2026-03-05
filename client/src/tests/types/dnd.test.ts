import {
  buildDraggableId,
  parseDraggableId,
  getDraggableType,
  getDraggableTypeForNode,
  PARENT_CONTAINER_TYPE,
  DRAGGABLE_TYPES,
} from '@/types/dnd';
import type { SectionNode, RowNode, ColumnNode, SimpleElementNode } from '@/types/elements';

describe('buildDraggableId', () => {
  it('builds a composite ID from type and numeric ID', () => {
    expect(buildDraggableId('section', 42)).toBe('section-42');
    expect(buildDraggableId('row', 17)).toBe('row-17');
    expect(buildDraggableId('column', 8)).toBe('column-8');
    expect(buildDraggableId('element', 103)).toBe('element-103');
  });
});

describe('parseDraggableId', () => {
  it('parses type and numeric ID from composite string', () => {
    expect(parseDraggableId('section-42')).toEqual({ type: 'section', id: 42 });
    expect(parseDraggableId('element-103')).toEqual({ type: 'element', id: 103 });
  });

  it('returns null for invalid format', () => {
    expect(parseDraggableId('invalid')).toBeNull();
    expect(parseDraggableId('')).toBeNull();
    expect(parseDraggableId('section-abc')).toBeNull();
    expect(parseDraggableId('unknown-42')).toBeNull();
  });

  it('returns null for non-positive IDs', () => {
    expect(parseDraggableId('section-0')).toBeNull();
    expect(parseDraggableId('section--1')).toBeNull();
  });
});

describe('getDraggableType', () => {
  it('returns the type portion of a draggable ID', () => {
    expect(getDraggableType('section-42')).toBe('section');
    expect(getDraggableType('row-17')).toBe('row');
  });

  it('returns null for invalid IDs', () => {
    expect(getDraggableType('invalid')).toBeNull();
  });
});

describe('PARENT_CONTAINER_TYPE', () => {
  it('maps each type to its parent container', () => {
    expect(PARENT_CONTAINER_TYPE.section).toBe('root');
    expect(PARENT_CONTAINER_TYPE.row).toBe('section');
    expect(PARENT_CONTAINER_TYPE.column).toBe('row');
    expect(PARENT_CONTAINER_TYPE.element).toBe('column');
  });
});

describe('DRAGGABLE_TYPES', () => {
  it('contains all four hierarchy levels', () => {
    expect(DRAGGABLE_TYPES).toEqual(['section', 'row', 'column', 'element']);
  });
});

describe('getDraggableTypeForNode', () => {
  const baseFields = {
    title: 'Test',
    blockSchema: { typeName: 'Test', label: 'Test', type: 'Test', title: '', summary: '' },
    obsoleteClassName: null,
    version: 1,
    canDelete: true,
    canPublish: true,
    canUnpublish: false,
    canCreate: true,
    statusFlags: {},
  };

  it('returns "section" for a section node', () => {
    const node: SectionNode = {
      ...baseFields,
      id: 1,
      parentId: 42,
      containerType: 'section',
      allowedTypes: null,
      children: null,
    };
    expect(getDraggableTypeForNode(node)).toBe('section');
  });

  it('returns "row" for a row node', () => {
    const node: RowNode = {
      ...baseFields,
      id: 2,
      parentId: 100,
      containerType: 'row',
      allowedTypes: null,
      children: null,
    };
    expect(getDraggableTypeForNode(node)).toBe('row');
  });

  it('returns "column" for a column node', () => {
    const node: ColumnNode = {
      ...baseFields,
      id: 3,
      parentId: 200,
      containerType: 'column',
      allowedTypes: null,
      children: null,
      gridSettings: {},
    };
    expect(getDraggableTypeForNode(node)).toBe('column');
  });

  it('returns "element" for a leaf node', () => {
    const node: SimpleElementNode = {
      ...baseFields,
      id: 4,
      parentId: 300,
    };
    expect(getDraggableTypeForNode(node)).toBe('element');
  });
});
