import {
  buildDraggableId,
  parseDraggableId,
  getDraggableType,
  PARENT_CONTAINER_TYPE,
  DRAGGABLE_TYPES,
} from '@/types/dnd';

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
