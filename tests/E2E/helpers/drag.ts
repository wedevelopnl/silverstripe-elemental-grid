import type { Locator, Page } from '@playwright/test';

/**
 * Scrolls a locator into view and returns its center coordinates.
 * Ensures the element is visible in the viewport before computing
 * coordinates, which is required for page.mouse operations.
 */
async function getCenter(locator: Locator): Promise<{ x: number; y: number }> {
  await locator.scrollIntoViewIfNeeded();
  const box = await locator.boundingBox();
  if (box === null) {
    throw new Error('Element not visible — cannot compute center for drag');
  }
  return { x: box.x + box.width / 2, y: box.y + box.height / 2 };
}

/**
 * Small delay for the browser event loop and React reconciliation.
 * dnd-kit processes pointer events synchronously but React state
 * updates are batched and rendered asynchronously.
 */
function tick(page: Page, ms = 100): Promise<void> {
  return page.waitForTimeout(ms);
}

/**
 * dnd-kit uses PointerSensor with an 8px activation threshold.
 * Playwright's built-in dragTo() fires HTML5 DragEvents which dnd-kit
 * ignores. Instead, we simulate raw pointer moves that exceed the
 * activation distance and follow the drag lifecycle.
 *
 * Timing delays between steps are critical: dnd-kit processes pointer
 * events to update internal state, then React re-renders to update
 * droppable positions used by collision detection. Without delays,
 * the pointer reaches the target before React has re-rendered, causing
 * stale collision results and dropped events.
 */

interface DragHandle {
  /** Release the mouse to complete the drop. */
  release: () => Promise<void>;
}

/**
 * Start a drag from `source` toward `target` and pause mid-drag,
 * hovering over the target. Returns a handle to release the mouse,
 * enabling mid-drag assertions (e.g. overlay visibility, drop-target
 * highlighting).
 *
 * Both `source` and `target` should be drag-handle locators
 * (or any visible element whose center is the desired pointer position).
 */
export async function startDrag(
  page: Page,
  source: Locator,
  target: Locator,
): Promise<DragHandle> {
  const from = await getCenter(source);
  const to = await getCenter(target);

  // Move to source center and press
  await page.mouse.move(from.x, from.y);
  await page.mouse.down();

  // Move 10px toward target to exceed PointerSensor's 8px activation threshold
  const dx = to.x - from.x;
  const dy = to.y - from.y;
  const dist = Math.sqrt(dx * dx + dy * dy);
  const activationX = from.x + (dx / dist) * 10;
  const activationY = from.y + (dy / dist) * 10;

  await page.mouse.move(activationX, activationY, { steps: 3 });

  // Let dnd-kit activate the drag and React render the DragOverlay
  await tick(page, 150);

  // Move to target center with many steps for smooth pointer tracking.
  // dnd-kit updates collision detection on each pointermove event.
  await page.mouse.move(to.x, to.y, { steps: 20 });

  // Let collision detection settle at the final position
  await tick(page, 150);

  return {
    release: async () => {
      await page.mouse.up();
      // Let React process the onDragEnd state update
      await tick(page, 150);
    },
  };
}

/**
 * Perform a complete drag-and-drop from `source` to `target`.
 *
 * Both `source` and `target` should be drag-handle locators
 * (or any visible element whose center is the desired pointer position).
 */
export async function performDrag(
  page: Page,
  source: Locator,
  target: Locator,
): Promise<void> {
  const handle = await startDrag(page, source, target);
  await handle.release();
}
