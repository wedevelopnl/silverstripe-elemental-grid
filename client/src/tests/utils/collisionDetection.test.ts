import type { DroppableContainer } from '@dnd-kit/core';

import {
  filterDroppablesByType,
  typedCollisionDetection,
} from '@/utils/collisionDetection';

/**
 * Creates a minimal DroppableContainer stub for testing the filter logic.
 * Only the `id` field is exercised by our collision detection strategy.
 */
function makeContainer(id: string): DroppableContainer {
  return {
    id,
    key: id,
    data: { current: undefined },
    disabled: false,
    node: { current: null },
    rect: { current: null },
  };
}

describe('filterDroppablesByType', () => {
  const containers = [
    makeContainer('section-1'),
    makeContainer('section-2'),
    makeContainer('row-10'),
    makeContainer('row-20'),
    makeContainer('column-5'),
    makeContainer('column-6'),
    makeContainer('element-100'),
    makeContainer('element-200'),
    makeContainer('root'),
  ];

  it('allows only row-* and section-* containers when dragging a row', () => {
    const result = filterDroppablesByType('row-17', containers);
    const ids = result.map((c) => String(c.id));

    expect(ids).toContain('row-10');
    expect(ids).toContain('row-20');
    expect(ids).toContain('section-1');
    expect(ids).toContain('section-2');
    expect(ids).not.toContain('column-5');
    expect(ids).not.toContain('element-100');
    expect(ids).not.toContain('root');
  });

  it('allows only section-* and non-typed (root) containers when dragging a section', () => {
    const result = filterDroppablesByType('section-1', containers);
    const ids = result.map((c) => String(c.id));

    expect(ids).toContain('section-1');
    expect(ids).toContain('section-2');
    expect(ids).toContain('root');
    expect(ids).not.toContain('row-10');
    expect(ids).not.toContain('column-5');
    expect(ids).not.toContain('element-100');
  });

  it('allows only element-* and column-* containers when dragging an element', () => {
    const result = filterDroppablesByType('element-100', containers);
    const ids = result.map((c) => String(c.id));

    expect(ids).toContain('element-100');
    expect(ids).toContain('element-200');
    expect(ids).toContain('column-5');
    expect(ids).toContain('column-6');
    expect(ids).not.toContain('section-1');
    expect(ids).not.toContain('row-10');
    expect(ids).not.toContain('root');
  });

  it('allows only column-* and row-* containers when dragging a column', () => {
    const result = filterDroppablesByType('column-5', containers);
    const ids = result.map((c) => String(c.id));

    expect(ids).toContain('column-5');
    expect(ids).toContain('column-6');
    expect(ids).toContain('row-10');
    expect(ids).toContain('row-20');
    expect(ids).not.toContain('section-1');
    expect(ids).not.toContain('element-100');
    expect(ids).not.toContain('root');
  });

  it('returns an empty array for an invalid active ID', () => {
    expect(filterDroppablesByType('invalid', containers)).toEqual([]);
    expect(filterDroppablesByType('', containers)).toEqual([]);
    expect(filterDroppablesByType('unknown-42', containers)).toEqual([]);
  });

  it('returns an empty array when droppable containers are empty', () => {
    expect(filterDroppablesByType('row-1', [])).toEqual([]);
    expect(filterDroppablesByType('section-1', [])).toEqual([]);
  });
});

describe('filterDroppablesByType — parent container matching', () => {
  it('includes parent container type for non-root draggables', () => {
    // A row's parent container type is 'section'
    // This tests the `parentType !== 'root' && containerType === parentType` branch
    const containers = [makeContainer('section-1'), makeContainer('column-5')];
    const result = filterDroppablesByType('row-10', containers);
    const ids = result.map((c) => String(c.id));

    expect(ids).toContain('section-1');
    expect(ids).not.toContain('column-5');
  });

  it('does not include typed containers as parent for sections', () => {
    // A section's parent is 'root' — so no typed container should match
    // as a parent. Only containers with unparseable IDs (null type) match.
    const containers = [
      makeContainer('section-2'),
      makeContainer('row-10'),
      makeContainer('column-5'),
      makeContainer('element-100'),
    ];
    const result = filterDroppablesByType('section-1', containers);
    const ids = result.map((c) => String(c.id));

    // Only sibling sections are included, no rows/columns/elements
    expect(ids).toEqual(['section-2']);
  });

  it('includes root container (null type) only for sections', () => {
    // 'root' is unparseable → containerType is null
    // Only sections (parentType === 'root') should accept null-typed containers
    const containers = [makeContainer('root')];

    expect(filterDroppablesByType('section-1', containers).length).toBe(1);
    expect(filterDroppablesByType('row-10', containers).length).toBe(0);
    expect(filterDroppablesByType('column-5', containers).length).toBe(0);
    expect(filterDroppablesByType('element-100', containers).length).toBe(0);
  });
});

describe('typedCollisionDetection', () => {
  it('returns empty collisions for an invalid active ID', () => {
    const result = typedCollisionDetection({
      active: {
        id: 'invalid',
        data: { current: undefined },
        rect: { current: { initial: null, translated: null } },
      },
      collisionRect: {
        width: 0,
        height: 0,
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
      droppableRects: new Map(),
      droppableContainers: [makeContainer('row-1')],
      pointerCoordinates: null,
    });

    expect(result).toEqual([]);
  });

  it('returns collisions for a valid active ID', () => {
    // When active ID is valid, typedCollisionDetection delegates to closestCenter
    // with filtered containers. Ensure it does NOT return empty for valid IDs.
    const container = makeContainer('row-20');
    const rect = { width: 100, height: 50, top: 0, left: 0, right: 100, bottom: 50 };
    const droppableRects = new Map<string, typeof rect>();
    droppableRects.set('row-20', rect);

    const result = typedCollisionDetection({
      active: {
        id: 'row-10',
        data: { current: undefined },
        rect: { current: { initial: rect, translated: rect } },
      },
      collisionRect: rect,
      droppableRects,
      droppableContainers: [container],
      pointerCoordinates: null,
    });

    // closestCenter should find row-20 as a collision target
    expect(result.length).toBeGreaterThan(0);
    expect(result[0].id).toBe('row-20');
  });
});
