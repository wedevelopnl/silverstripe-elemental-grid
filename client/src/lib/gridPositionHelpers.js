/**
 * Grid Position Helpers
 *
 * Utilities for translating grid positions (left/right/above/below) into
 * sequential insertion points that SilverStripe's ElementalList expects.
 */

/**
 * Analyzes the grid layout to understand row structure and element relationships
 *
 * @param {Array} blocks - Array of element blocks
 * @returns {Object} Grid analysis with rows, positions, and relationships
 */
export const analyzeGridLayout = (blocks) => {
  if (!blocks || !blocks.length) {
    return { rows: [], elementPositions: new Map(), rowMap: new Map() };
  }

  const elementPositions = new Map();
  const rowMap = new Map();
  const rows = [];
  let currentRow = [];
  let currentRowIndex = 0;

  blocks.forEach((block, index) => {
    const gridSchema = block.blockSchema && block.blockSchema.grid;
    const isRow = gridSchema && gridSchema.isRow;

    // Store element position info
    elementPositions.set(block.id, {
      sequentialIndex: index,
      rowIndex: currentRowIndex,
      isRow,
      block
    });

    if (isRow) {
      // Row element - finish current row and start new one
      if (currentRow.length > 0) {
        rows.push([...currentRow]);
        currentRow = [];
        currentRowIndex++;
      }

      // Row element goes in its own row
      rows.push([block]);
      rowMap.set(currentRowIndex, [block]);
      currentRowIndex++;
      currentRow = [];
    } else {
      // Regular element - add to current row
      currentRow.push(block);
    }
  });

  // Add final row if it has elements
  if (currentRow.length > 0) {
    rows.push(currentRow);
    rowMap.set(currentRowIndex, currentRow);
  }

  return {
    rows,
    elementPositions,
    rowMap
  };
};

/**
 * Calculates the insertion point for a grid position drop
 *
 * @param {Object} dropResult - The drop result from GridDropZone or RowDropZone
 * @param {Array} blocks - Array of element blocks
 * @param {Object} draggedItem - The item being dragged
 * @returns {Object} Insertion details with dropAfterID and metadata
 */
export const calculateGridInsertion = (dropResult, blocks, draggedItem) => {
  const { target, gridPosition, rowPosition, isRowDrop } = dropResult;
  const gridAnalysis = analyzeGridLayout(blocks);
  const targetElementInfo = gridAnalysis.elementPositions.get(target);

  if (!targetElementInfo) {
    // Fallback: insert at end
    return {
      dropAfterID: blocks.length > 0 ? blocks[blocks.length - 1].id : '0',
      insertPosition: 'end',
      gridMetadata: { position: gridPosition || rowPosition, isRowDrop }
    };
  }

  const targetRowIndex = targetElementInfo.rowIndex;
  const targetRow = gridAnalysis.rows[targetRowIndex];

  if (isRowDrop || rowPosition) {
    // Row-level drop (above/below)
    return calculateRowInsertion(dropResult, gridAnalysis, targetElementInfo, blocks);
  } else {
    // Grid-level drop (left/right within row)
    return calculateColumnInsertion(dropResult, gridAnalysis, targetElementInfo, targetRow, blocks);
  }
};

/**
 * Calculates insertion for row-level drops (above/below)
 */
const calculateRowInsertion = (dropResult, gridAnalysis, targetElementInfo, blocks) => {
  const { rowPosition } = dropResult;
  const targetRowIndex = targetElementInfo.rowIndex;

  if (rowPosition === 'above') {
    // Insert before the first element in the target row
    const targetRow = gridAnalysis.rows[targetRowIndex];
    const firstElementInRow = targetRow[0];
    const firstElementIndex = blocks.findIndex(block => block.id === firstElementInRow.id);

    if (firstElementIndex === 0) {
      // Target is first element overall
      return {
        dropAfterID: '0',
        insertPosition: 'beginning',
        gridMetadata: { position: rowPosition, isRowDrop: true }
      };
    } else {
      // Insert after previous element
      return {
        dropAfterID: blocks[firstElementIndex - 1].id,
        insertPosition: 'before_row',
        gridMetadata: { position: rowPosition, isRowDrop: true }
      };
    }
  } else {
    // Insert after the last element in the target row
    const targetRow = gridAnalysis.rows[targetRowIndex];
    const lastElementInRow = targetRow[targetRow.length - 1];

    return {
      dropAfterID: lastElementInRow.id,
      insertPosition: 'after_row',
      gridMetadata: { position: rowPosition, isRowDrop: true }
    };
  }
};

/**
 * Calculates insertion for column-level drops (left/right within row)
 */
const calculateColumnInsertion = (dropResult, gridAnalysis, targetElementInfo, targetRow, blocks) => {
  const { gridPosition, target } = dropResult;
  const targetElementIndex = targetRow.findIndex(element => element.id === target);

  if (gridPosition === 'left') {
    // Insert to the left of target element
    if (targetElementIndex === 0) {
      // Target is first in row - insert after previous row's last element
      const targetRowIndex = targetElementInfo.rowIndex;
      if (targetRowIndex === 0) {
        // First row - insert at beginning
        return {
          dropAfterID: '0',
          insertPosition: 'beginning',
          gridMetadata: { position: gridPosition, isRowDrop: false }
        };
      } else {
        // Insert after previous row's last element
        const previousRow = gridAnalysis.rows[targetRowIndex - 1];
        const previousRowLastElement = previousRow[previousRow.length - 1];
        return {
          dropAfterID: previousRowLastElement.id,
          insertPosition: 'left_of_first',
          gridMetadata: { position: gridPosition, isRowDrop: false }
        };
      }
    } else {
      // Insert after the element to the left of target
      const leftElement = targetRow[targetElementIndex - 1];
      return {
        dropAfterID: leftElement.id,
        insertPosition: 'left_within_row',
        gridMetadata: { position: gridPosition, isRowDrop: false }
      };
    }
  } else if (gridPosition === 'right') {
    // Insert to the right of target element
    return {
      dropAfterID: target,
      insertPosition: 'right_within_row',
      gridMetadata: { position: gridPosition, isRowDrop: false }
    };
  }

  // Fallback
  return {
    dropAfterID: target,
    insertPosition: 'fallback',
    gridMetadata: { position: gridPosition, isRowDrop: false }
  };
};

/**
 * Checks if two elements are in the same row
 *
 * @param {String} elementId1
 * @param {String} elementId2
 * @param {Array} blocks
 * @returns {Boolean}
 */
export const areElementsInSameRow = (elementId1, elementId2, blocks) => {
  const gridAnalysis = analyzeGridLayout(blocks);
  const element1Info = gridAnalysis.elementPositions.get(elementId1);
  const element2Info = gridAnalysis.elementPositions.get(elementId2);

  if (!element1Info || !element2Info) {
    return false;
  }

  return element1Info.rowIndex === element2Info.rowIndex;
};

/**
 * Gets all elements in the same row as the given element
 *
 * @param {String} elementId
 * @param {Array} blocks
 * @returns {Array}
 */
export const getRowElements = (elementId, blocks) => {
  const gridAnalysis = analyzeGridLayout(blocks);
  const elementInfo = gridAnalysis.elementPositions.get(elementId);

  if (!elementInfo) {
    return [];
  }

  return gridAnalysis.rows[elementInfo.rowIndex] || [];
};

/**
 * Validates a grid drop operation
 *
 * @param {Object} dropResult
 * @param {Array} blocks
 * @param {Object} draggedItem
 * @returns {Object} Validation result
 */
export const validateGridDrop = (dropResult, blocks, draggedItem) => {
  const { target, gridPosition, rowPosition } = dropResult;

  // Basic validation
  if (!target || (!gridPosition && !rowPosition)) {
    return {
      isValid: false,
      reason: 'Invalid drop target or position'
    };
  }

  // Check if trying to drop element on itself
  if (draggedItem && draggedItem.id === target) {
    return {
      isValid: false,
      reason: 'Cannot drop element on itself'
    };
  }

  // Check if target element exists
  const targetExists = blocks.some(block => block.id === target);
  if (target !== '0' && !targetExists) {
    return {
      isValid: false,
      reason: 'Target element not found'
    };
  }

  return {
    isValid: true,
    reason: 'Valid drop operation'
  };
};
