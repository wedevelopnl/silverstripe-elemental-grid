import Injector from 'lib/Injector';
import React from 'react';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';
import ReactGridDropZone from 'components/ReactGridDropZone';

// Helper function to extract numeric ID from DOM element IDs  
const extractNumericId = (domElementId) => {
  if (!domElementId) return null;

  // If it's already numeric, return as string
  if (/^\d+$/.test(domElementId)) {
    return domElementId;
  }

  // Extract numeric part from DOM element IDs like "element-icon-71", "columnSize-71", etc.
  const match = domElementId.match(/(\d+)$/);
  if (match) {
    return match[1];
  }

  return null;
};

// Helper function to detect element type (row vs regular element)
const isRowElement = (element) => {
  return element && element.classList && element.classList.contains('is-row');
};

// Helper function to create grid drop zones
const createGridDropZone = (position, element, index) => {
  const zone = document.createElement('div');
  zone.className = `grid-drop-zone grid-drop-zone--${position}`;
  zone.setAttribute('data-position', position);
  zone.setAttribute('data-element-index', index);
  zone.setAttribute('data-element-id', element.getAttribute('data-element-id') || element.id);
  
  const inner = document.createElement('div');
  inner.className = 'grid-drop-zone__inner';
  
  const button = document.createElement('button');
  button.className = 'grid-drop-zone__button';
  button.type = 'button';
  
  const icon = document.createElement('span');
  icon.className = 'grid-drop-zone__icon';
  icon.textContent = position === 'left' ? '←' : '→';
  
  const label = document.createElement('span');
  label.className = 'grid-drop-zone__label';
  label.textContent = position === 'left' ? 'LEFT' : 'RIGHT';
  
  button.appendChild(icon);
  button.appendChild(label);
  inner.appendChild(button);
  zone.appendChild(inner);
  
  return zone;
};

// Helper function to create row drop zones
const createRowDropZone = (position, element, index) => {
  const zone = document.createElement('div');
  zone.className = `row-drop-zone row-drop-zone--${position}`;
  zone.setAttribute('data-position', position);
  zone.setAttribute('data-element-index', index);
  zone.setAttribute('data-element-id', element.getAttribute('data-element-id') || element.id);
  
  const inner = document.createElement('div');
  inner.className = 'row-drop-zone__inner';
  
  const line = document.createElement('div');
  line.className = 'row-drop-zone__line';
  
  const button = document.createElement('button');
  button.className = 'row-drop-zone__button';
  button.type = 'button';
  
  const icon = document.createElement('span');
  icon.className = 'row-drop-zone__icon';
  icon.textContent = '+';
  
  const label = document.createElement('span');
  label.className = 'row-drop-zone__label';
  label.textContent = position === 'above' ? 'Add Above' : 'Add Below';
  
  button.appendChild(icon);
  button.appendChild(label);
  inner.appendChild(line);
  inner.appendChild(button);
  zone.appendChild(inner);
  
  return zone;
};

// Function to add grid drop zones around elements
const addGridDropZonesAroundElement = (element, index) => {
  const elementWrapper = element.closest('.element-editor__element-holder') || element.parentElement;
  
  if (!elementWrapper) return;
  
  // Make the element container position relative to contain the absolute positioned zones
  if (elementWrapper.style.position !== 'relative') {
    elementWrapper.style.position = 'relative';
  }
  
  if (isRowElement(element)) {
    // For row elements: add top and bottom drop zones
    const aboveZone = createRowDropZone('above', element, index);
    const belowZone = createRowDropZone('below', element, index);
    
    elementWrapper.parentElement.insertBefore(aboveZone, elementWrapper);
    elementWrapper.parentElement.insertBefore(belowZone, elementWrapper.nextSibling);
  } else {
    // For regular elements: add left and right drop zones as overlays inside the element container
    const leftZone = createGridDropZone('left', element, index);
    elementWrapper.appendChild(leftZone);

    const rightZone = createGridDropZone('right', element, index);
    elementWrapper.appendChild(rightZone);
  }
};

// Remove console.log statements to fix linting
// console.log('[GRID DEBUG] ========== GRID BUNDLE LOADING (REACT DND INTEGRATION) ==========');
// console.log('[GRID DEBUG] Core components loaded for alongside implementation with React DnD zones');

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

// Register core grid components (no complex overrides)
Injector.component.registerMany({
  AddBlockToBottomButton,
  AddBlockToTopButton,
  ColumnSize,
  ReactGridDropZone,
});

// Cache for row elements to quickly restore during drag operations
const rowElementsCache = new Map();

// Throttle function to prevent excessive re-application
const throttle = (func, delay) => {
  let timeoutId;
  let lastExecTime = 0;
  return function (...args) {
    const currentTime = Date.now();

    if (currentTime - lastExecTime > delay) {
      func.apply(this, args);
      lastExecTime = currentTime;
    } else {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => {
        func.apply(this, args);
        lastExecTime = Date.now();
      }, delay - (currentTime - lastExecTime));
    }
  };
};

// Function to apply Bootstrap grid classes to wrapper divs (correct Bootstrap implementation)
const applyGridClassesToWrapper = (wrapperDiv, size, offset) => {
  // Remove existing grid classes from wrapper
  wrapperDiv.className = wrapperDiv.className.replace(/\bcol-lg-\d+\b/g, '');
  wrapperDiv.className = wrapperDiv.className.replace(/\boffset-lg-\d+\b/g, '');

  // Add new grid classes to wrapper (direct child of .row)
  wrapperDiv.classList.add(`col-lg-${size}`);

  if (offset && offset > 0) {
    wrapperDiv.classList.add(`offset-lg-${offset}`);
  }

  // Add Bootstrap no horizontal padding class to prevent padding conflicts
  if (!wrapperDiv.classList.contains('px-0')) {
    wrapperDiv.classList.add('px-0');
  }
};

// Function to clean up any incorrectly applied grid classes
const cleanupIncorrectGridClasses = () => {
  // Remove grid classes from the elemental-editor-list (wrong container)
  const editorList = document.querySelector('.elemental-editor-list');
  if (editorList) {
    editorList.className = editorList.className.replace(/\bcol-lg-\d+\b/g, '');
    editorList.className = editorList.className.replace(/\boffset-lg-\d+\b/g, '');
    editorList.classList.remove('px-0'); // Remove px-0 from wrong container
  }

  // Remove grid classes from element cards (wrong level - should be on wrapper divs)
  const elementCards = document.querySelectorAll('.element-editor__element');
  elementCards.forEach(elementCard => {
    // Only remove grid classes if they exist, preserving other classes like 'is-row'
    if (elementCard.className.match(/\bcol-lg-\d+\b/)) {
      elementCard.className = elementCard.className.replace(/\bcol-lg-\d+\b/g, '');
    }
    if (elementCard.className.match(/\boffset-lg-\d+\b/)) {
      elementCard.className = elementCard.className.replace(/\boffset-lg-\d+\b/g, '');
    }
    elementCard.classList.remove('px-0'); // Remove px-0 from element cards
  });
};

// Function to move grid controls into their respective cards
const moveGridControlsIntoCards = () => {
  // PAUSE grid manipulation during drag operations to prevent interference
  if (window.pauseGridClassManipulation || window.isDraggingElement) {
    return;
  }
  // First clean up any incorrectly applied grid classes
  cleanupIncorrectGridClasses();

  // Add row class to the elemental-editor-list container to enable Bootstrap flexbox grid
  const elementalEditorList = document.querySelector('.elemental-editor-list');
  if (elementalEditorList && !elementalEditorList.classList.contains('row')) {
    elementalEditorList.classList.add('row');
  }

  const gridControls = document.querySelectorAll('.column-size-controls');

  gridControls.forEach((control) => {
    // Find the element ID from the control inputs
    const sizeSelect = control.querySelector('[id^="columnSize-"]');
    const offsetSelect = control.querySelector('[id^="columnOffset-"]');
    if (!sizeSelect || !offsetSelect) {
      return;
    }

    // Check if control is already inside an element card
    const existingElementCard = control.closest('.element-editor__element');
    if (existingElementCard) {
      // Find the wrapper div (parent of element card) to apply grid classes
      const wrapperDiv = existingElementCard.parentElement;
      if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
        applyGridClassesToWrapper(wrapperDiv, sizeSelect.value, offsetSelect.value);
      }
      return;
    }

    // The control is rendered as a sibling to the element card
    // Look for the element card that's a sibling to this control
    const parent = control.parentElement;
    if (!parent) {
      return;
    }

    const elementCard = Array.from(parent.children).find(child =>
      child.classList.contains('element-editor__element')
    );

    if (!elementCard) {
      return;
    }

    // Move the control into the card
    elementCard.appendChild(control);

    // Apply grid classes to the wrapper div (parent of element card) instead of element card itself
    const wrapperDiv = elementCard.parentElement;
    if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
      applyGridClassesToWrapper(wrapperDiv, sizeSelect.value, offsetSelect.value);
    }

    // Listen for changes to the dropdowns and update classes
    if (!sizeSelect.hasAttribute('data-grid-listener')) {
      sizeSelect.setAttribute('data-grid-listener', 'true');
      sizeSelect.addEventListener('change', (e) => {
        if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
          applyGridClassesToWrapper(wrapperDiv, e.target.value, offsetSelect.value);
        }
      });
    }

    if (!offsetSelect.hasAttribute('data-grid-listener')) {
      offsetSelect.setAttribute('data-grid-listener', 'true');
      offsetSelect.addEventListener('change', (e) => {
        if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
          applyGridClassesToWrapper(wrapperDiv, sizeSelect.value, e.target.value);
        }
      });
    }
  });

  // Identify and handle row elements (elements without grid controls)
  const allElementCards = document.querySelectorAll('.element-editor__element');
  allElementCards.forEach((elementCard) => {
    const hasGridControls = elementCard.querySelector('.column-size-controls');
    const titleElement = elementCard.querySelector('.element-editor-header__title');
    const isRowElement = !hasGridControls ||
                         (titleElement && titleElement.textContent.includes('Row block'));

    if (isRowElement) {
      // Add is-row class for identification to the element card
      if (!elementCard.classList.contains('is-row')) {
        elementCard.classList.add('is-row');
      }

      // Explicitly hide the element-editor-summary to prevent "No preview available" from showing
      const summaryElement = elementCard.querySelector('.element-editor-summary');
      if (summaryElement && summaryElement.style.display !== 'none') {
        summaryElement.style.display = 'none';
      }

      // Force row elements to be full-width breaks by applying classes to wrapper
      const wrapperDiv = elementCard.parentElement;
      if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
        applyGridClassesToWrapper(wrapperDiv, 12, 0);
      }
    }
  });
};

// Function to quickly restore row element styling
const restoreRowElementStyling = () => {
  // PAUSE during drag operations to prevent interference
  if (window.pauseGridClassManipulation || window.isDraggingElement) {
    return;
  }
  const allElementCards = document.querySelectorAll('.element-editor__element');
  allElementCards.forEach((elementCard) => {
    const hasGridControls = elementCard.querySelector('.column-size-controls');
    const titleElement = elementCard.querySelector('.element-editor-header__title');
    const isRowElement = !hasGridControls ||
                         (titleElement && titleElement.textContent.includes('Row block'));

    if (isRowElement) {
      // Add is-row class if missing
      if (!elementCard.classList.contains('is-row')) {
        elementCard.classList.add('is-row');
      }

      // Explicitly hide the element-editor-summary to prevent "No preview available" from showing
      const summaryElement = elementCard.querySelector('.element-editor-summary');
      if (summaryElement && summaryElement.style.display !== 'none') {
        summaryElement.style.display = 'none';
      }

      // Restore grid classes to wrapper if needed
      const wrapperDiv = elementCard.parentElement;
      if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
        if (!wrapperDiv.classList.contains('col-lg-12')) {
          applyGridClassesToWrapper(wrapperDiv, 12, 0);
        }
      }
    }
  });
};

// Throttled version for high-frequency events
const throttledRestoreRowStyling = throttle(restoreRowElementStyling, 100);
const throttledMoveGridControls = throttle(moveGridControlsIntoCards, 200);

// Create a higher-order component that enhances the existing Element with grid functionality
const withGridFunctionality = (OriginalElement) => {
  const GridEnhancedElement = (props) => {
    // Get the ColumnSize component from Injector
    const ColumnSizeComponent = Injector.component.get('ColumnSize');

    // Check if this element needs grid functionality
    const { element } = props;
    const hasGridSchema = element && element.blockSchema && element.blockSchema.grid;
    const isNotRow = hasGridSchema && !element.blockSchema.grid.isRow;
    const isRow = hasGridSchema && element.blockSchema.grid.isRow;

    // NEW: Determine if this is a row element (for declarative styling)
    const shouldBeRowElement = isRow || (!hasGridSchema &&
      (element.blockSchema.typeName === 'ElementRow' ||
       (element.title && element.title.includes('Row')) ||
       (element.blockSchema.title && element.blockSchema.title.includes('Row'))));

    // Hook into drag lifecycle to re-apply grid classes
    const originalOnDragEnd = props.onDragEnd;
    const enhancedOnDragEnd = React.useCallback((itemID, dropAfterID) => {
      // Call the original onDragEnd first with the correct parameters
      if (originalOnDragEnd) {
        originalOnDragEnd(itemID, dropAfterID);
      }

      // Don't immediately re-apply grid classes - let the global drag end handler do it
      // This prevents double application and timing issues
    }, [originalOnDragEnd]);

    // NEW: Enhanced drag start handler to preserve row state
    const originalOnDragStart = props.onDragStart;
    const enhancedOnDragStart = React.useCallback((e) => {
      if (originalOnDragStart) {
        originalOnDragStart(e);
      }

      // Mark row elements during drag start for preservation
      if (shouldBeRowElement) {
        // Add data attribute to help with restoration
        setTimeout(() => {
          const elementCard = document.querySelector(`[data-element-id="${element.id}"]`);
          const elementCardElement = elementCard && elementCard.closest('.element-editor__element');
          if (elementCardElement) {
            elementCardElement.setAttribute('data-grid-row-element', 'true');
          }
        }, 0);
      }
    }, [originalOnDragStart, shouldBeRowElement, element.id]);

    // NEW: Enhanced drag over handler for immediate restoration
    const originalOnDragOver = props.onDragOver;
    const enhancedOnDragOver = React.useCallback((e) => {
      if (originalOnDragOver) {
        originalOnDragOver(e);
      }

      // If this is a row element being dragged over, immediately restore styling
      if (shouldBeRowElement && window.isDraggingElement) {
        restoreRowElementStyling();
      }
    }, [originalOnDragOver, shouldBeRowElement]);

    // NEW: Create enhanced props with drag handlers and declarative classes
    const enhancedProps = {
      ...props,
      onDragEnd: enhancedOnDragEnd,
      onDragStart: enhancedOnDragStart,
      onDragOver: enhancedOnDragOver,
      // NEW: Add data attributes for better identification
      'data-element-id': element.id,
      'data-is-row': shouldBeRowElement,
      // NEW: Add CSS classes declaratively to help persist through React re-renders
      className: `${props.className || ''} ${shouldBeRowElement ? 'grid-row-element' : 'grid-regular-element'}`.trim()
    };

    // Render the original element with enhanced props
    const originalElement = React.createElement(OriginalElement, enhancedProps);

    // Post-render layout effect to ensure grid classes are applied before browser paint
    React.useLayoutEffect(() => {
      // Apply grid functionality for regular elements
      if (isNotRow && ColumnSizeComponent) {
        // Apply immediately before browser paint to prevent visual shifts
        moveGridControlsIntoCards();
      }

      // NEW: Apply row styling for row elements
      if (shouldBeRowElement) {
        setTimeout(() => {
          const elementCard = document.querySelector(`[data-element-id="${element.id}"]`);
          const elementCardElement = elementCard && elementCard.closest('.element-editor__element');
          if (elementCardElement) {
            // Ensure row class is applied declaratively through DOM
            if (!elementCardElement.classList.contains('is-row')) {
              elementCardElement.classList.add('is-row');
            }

            // Explicitly hide the element-editor-summary to prevent "No preview available" from showing
            const summaryElement = elementCardElement.querySelector('.element-editor-summary');
            if (summaryElement && summaryElement.style.display !== 'none') {
              summaryElement.style.display = 'none';
            }

            // Mark as processed to help MutationObserver
            elementCardElement.setAttribute('data-grid-processed', 'true');

            // Apply wrapper grid classes
            const wrapperDiv = elementCardElement.parentElement;
            if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
              applyGridClassesToWrapper(wrapperDiv, 12, 0);
            }
          }
        }, 0);
      }
    }, [element.id, isNotRow, shouldBeRowElement, ColumnSizeComponent]);

    // If this is a row element, return with row styling only
    if (shouldBeRowElement) {
      return originalElement;
    }

    // If this element doesn't need grid functionality, return as-is
    if (!isNotRow || !ColumnSizeComponent) {
      return originalElement;
    }

    // Add grid functionality with proper onChange handlers
    const gridData = element.blockSchema.grid.column || {};

    const handleChangeSize = () => {
      // The GraphQL mutation will handle the update
    };

    const handleChangeOffset = () => {
      // The GraphQL mutation will handle the update
    };

    const gridComponent = React.createElement(ColumnSizeComponent, {
      elementId: element.id,
      size: gridData.size || 12,
      defaultViewport: gridData.defaultViewport || 'LG',
      gridColumns: element.blockSchema.grid.gridColumns || 12,
      offset: gridData.offset || 0,
      onChangeSize: handleChangeSize,
      onChangeOffset: handleChangeOffset,
      id: `grid-${element.id}`,
    });

    // Return enhanced element with grid controls
    return React.createElement(React.Fragment, null, originalElement, gridComponent);
  };

  GridEnhancedElement.displayName = `GridEnhanced(${OriginalElement.displayName || OriginalElement.name || 'Element'})`;
  return GridEnhancedElement;
};

// Global function to force re-application of grid classes (can be called from anywhere)
window.reapplyGridClasses = () => {
  moveGridControlsIntoCards();
};

// Add event listeners for drag operations
const addDragEventListeners = () => {
  // Listen for drag start events
  document.addEventListener('dragstart', (e) => {
    if (e.target.closest('.element-editor__element')) {
      // Mark that we're in a drag operation
      window.isDraggingElement = true;

      // Capture the dragged element ID for our drop zones
      const draggedElement = e.target.closest('.element-editor__element');
      if (draggedElement) {
        // Debug: log all attributes to see what's available
        console.log('[GRID DEBUG] Dragged element attributes:', {
          tagName: draggedElement.tagName,
          className: draggedElement.className,
          id: draggedElement.id,
          attributes: Array.from(draggedElement.attributes).map(attr => `${attr.name}="${attr.value}"`),
          innerHTML: `${draggedElement.innerHTML.substring(0, 500)}...`
        });

        // Look for all elements with ID attributes to understand the structure
        const elementsWithIds = draggedElement.querySelectorAll('[id]');
        console.log('[GRID DEBUG] All child elements with IDs:');
        Array.from(elementsWithIds).forEach((el, index) => {
          console.log(`  [${index}] ${el.tagName} id="${el.id}" class="${el.className}"`);
        });

        // Look for any data attributes that might contain the block ID
        const allElements = [draggedElement, ...draggedElement.querySelectorAll('*')];
        const dataAttributes = [];
        allElements.forEach(el => {
          Array.from(el.attributes).forEach(attr => {
            if (attr.name.startsWith('data-')) {
              dataAttributes.push({ element: el.tagName, attribute: attr.name, value: attr.value });
            }
          });
        });
        console.log('[GRID DEBUG] All data attributes in dragged element:');
        dataAttributes.forEach((attr, index) => {
          console.log(`  [${index}] ${attr.element} ${attr.attribute}="${attr.value}"`);
        });

        const elementId = draggedElement.getAttribute('data-element-id') ||
                         draggedElement.getAttribute('data-id') ||
                         draggedElement.getAttribute('data-block-id') ||
                         draggedElement.getAttribute('data-element') ||
                         draggedElement.id;
        const numericElementId = extractNumericId(elementId);
        window.currentDraggedElement = numericElementId;
        console.log('[GRID DEBUG] Captured dragged element ID on drag start:', elementId, '-> converted to numeric:', numericElementId);

        // If still no ID, try to find it in child elements
        if (!elementId) {
          const childWithId = draggedElement.querySelector('[data-element-id], [data-id], [data-block-id], [id]');
          if (childWithId) {
            const childId = childWithId.getAttribute('data-element-id') ||
                           childWithId.getAttribute('data-id') ||
                           childWithId.getAttribute('data-block-id') ||
                           childWithId.id;
            const numericChildId = extractNumericId(childId);
            window.currentDraggedElement = numericChildId;
            console.log('[GRID DEBUG] Found element ID in child element:', childId, '-> converted to numeric:', numericChildId);
          }
        }
      }

      // Add fallback class for browsers without :has() support
      const elementalList = document.querySelector('.elemental-editor-list');
      if (elementalList) {
        elementalList.classList.add('dragging-active');
      }

      // STOP grid class manipulation during drag to prevent interference
      window.pauseGridClassManipulation = true;

      // Cache current row elements state
      rowElementsCache.clear();
      document.querySelectorAll('.element-editor__element.is-row').forEach((row) => {
        rowElementsCache.set(row, {
          hasIsRowClass: row.classList.contains('is-row'),
          wrapperGridClasses: row.parentElement ? row.parentElement.className : ''
        });
      });
    }
  });

  // Listen for drag end events
  document.addEventListener('dragend', (e) => {
    if (e.target.closest('.element-editor__element')) {
      // Clear the drag flag
      window.isDraggingElement = false;
      window.pauseGridClassManipulation = false;

      // Clear the captured element ID
      window.currentDraggedElement = null;

      // Remove fallback class for browsers without :has() support
      const elementalList = document.querySelector('.elemental-editor-list');
      if (elementalList) {
        elementalList.classList.remove('dragging-active');
      }

      // Re-apply grid classes after a short delay to ensure DOM is stable
      setTimeout(() => {
        moveGridControlsIntoCards();
      }, 100);

      // Clear cache
      setTimeout(() => {
        rowElementsCache.clear();
      }, 1000);
    }
  });

  // Listen for drop events
  document.addEventListener('drop', (e) => {
    if (e.target.closest('.elemental-editor-list')) {
      // Re-apply grid classes immediately using requestAnimationFrame
      requestAnimationFrame(() => {
        moveGridControlsIntoCards();
      });
    }
  });

  // Listen for drag over events to catch styling loss during hover
  document.addEventListener('dragover', (e) => {
    if (window.isDraggingElement && e.target.closest('.elemental-editor-list')) {
      // Throttled restoration to prevent excessive calls
      throttledRestoreRowStyling();
    }
  });

  // Listen for drag enter events for immediate restoration
  document.addEventListener('dragenter', (e) => {
    if (window.isDraggingElement) {
      const targetElement = e.target.closest('.element-editor__element');
      if (targetElement && targetElement.classList.contains('is-row')) {
        // Immediately restore styling for row elements being hovered
        restoreRowElementStyling();
      }
    }
  });

  // Listen for drag leave events
  document.addEventListener('dragleave', (e) => {
    if (window.isDraggingElement && e.target.closest('.element-editor__element')) {
      // Small delay restoration to catch React re-renders
      setTimeout(() => {
        if (window.isDraggingElement) {
          throttledRestoreRowStyling();
        }
      }, 50);
    }
  });
};

// Hover bar enhancements removed - we now use our own drop zones for visual feedback

// Inject grid-aware drop zones around existing elements
const injectGridDropZones = () => {
  console.log('[GRID DEBUG] Injecting grid-aware drop zones...');

  const elementList = document.querySelector('.elemental-editor-list');
  if (!elementList) {
    console.log('[GRID DEBUG] No elemental editor list found');
    return;
  }

  const elements = elementList.querySelectorAll('.element-editor__element');
  console.log('[GRID DEBUG] Found', elements.length, 'elements to add grid zones to');

  elements.forEach((element, index) => {
    // Only add zones if not already present
    if (!element.parentElement.querySelector('.grid-drop-zone')) {
      addGridDropZonesAroundElement(element, index);
    }
  });
};


// insertAfter polyfill
const insertAfter = (newNode, referenceNode) => {
  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
};

// Trigger existing SilverStripe hover bar functionality
const triggerHoverBarClick = (hoverBar) => {
  if (!hoverBar) return;

  console.log('[GRID DEBUG] Attempting to trigger hover bar click');

  // Find the button inside the hover bar
  const hoverButton = hoverBar.querySelector('.element-editor__hover-bar-area');
  if (hoverButton) {
    console.log('[GRID DEBUG] Found hover bar button, triggering click');
    hoverButton.click();
  } else {
    console.log('[GRID DEBUG] No hover bar button found');
  }
};

// Helper function to get element ID from any element using our detection method
const getElementIdFromElement = (element) => {
  if (!element) return null;

  // Try direct attributes first
  const directId = element.getAttribute('data-element-id') ||
                  element.getAttribute('data-id') ||
                  element.getAttribute('data-block-id') ||
                  element.getAttribute('data-element') ||
                  element.id;

  if (directId) return extractNumericId(directId);

  // Try to find ID in child elements
  const childWithId = element.querySelector('[data-element-id], [data-id], [data-block-id], [id]');
  if (childWithId) {
    const childId = childWithId.getAttribute('data-element-id') ||
                   childWithId.getAttribute('data-id') ||
                   childWithId.getAttribute('data-block-id') ||
                   childWithId.id;
    return extractNumericId(childId);
  }

  return null;
};



// Calculate insertion position for grid drop zones
const calculateGridInsertionPosition = (position, targetElement) => {
  const targetElementId = getElementIdFromElement(targetElement);
  const elementWrapper = targetElement.parentElement;

  console.log('[GRID DEBUG] Calculating insertion for position:', position, 'target:', targetElementId);

  if (!elementWrapper) {
    console.warn('[GRID DEBUG] No element wrapper found');
    // Find any element to use as reference instead of null
    const anyElement = document.querySelector('.element-editor__element');
    const anyElementId = getElementIdFromElement(anyElement);
    return { insertAfterElementId: anyElementId || 'fallback', dropSpot: 'bottom' };
  }

  // Find all elements in the list to understand positioning
  const elementsList = elementWrapper.parentElement;
  const allElements = Array.from(elementsList.children).filter(child =>
    child.querySelector('.element-editor__element')
  );

  const currentIndex = allElements.findIndex(element =>
    element.querySelector('.element-editor__element') === targetElement
  );

  console.log('[GRID DEBUG] Current element index:', currentIndex, 'of', allElements.length);

  switch (position) {
    case 'left':
      // Insert before this element (same row, left position)
      if (currentIndex > 0) {
        const prevElement = allElements[currentIndex - 1].querySelector('.element-editor__element');
        const prevElementId = getElementIdFromElement(prevElement);
        return { insertAfterElementId: prevElementId, dropSpot: 'bottom' };
      }
      // For inserting at the beginning, we need a valid element ID instead of null
      const firstElement = allElements[0].querySelector('.element-editor__element');
      const firstElementId = getElementIdFromElement(firstElement);
      return { insertAfterElementId: firstElementId, dropSpot: 'top' };

    case 'right':
      // Insert after this element (same row, right position)
      return { insertAfterElementId: targetElementId, dropSpot: 'bottom' };

    case 'above':
      // Insert before this element (new row above)
      if (currentIndex > 0) {
        const prevElement = allElements[currentIndex - 1].querySelector('.element-editor__element');
        const prevElementId = getElementIdFromElement(prevElement);
        return { insertAfterElementId: prevElementId, dropSpot: 'bottom' };
      }
      // For inserting at the beginning, we need a valid element ID instead of null
      const firstElementForAbove = allElements[0].querySelector('.element-editor__element');
      const firstElementIdForAbove = getElementIdFromElement(firstElementForAbove);
      return { insertAfterElementId: firstElementIdForAbove, dropSpot: 'top' };

    case 'below':
      // Insert after this element (new row below)
      return { insertAfterElementId: targetElementId, dropSpot: 'bottom' };

    default:
      console.warn('[GRID DEBUG] Unknown position:', position);
      return { insertAfterElementId: targetElementId || 'fallback', dropSpot: 'bottom' };
  }
};

// Old manual drag/drop event handling removed - React DnD components handle this now

// Trigger SilverStripe's drag end handler with the correct parameters
const triggerSilverStripeDragEnd = (draggedElementId, insertAfterElementId) => {
  console.log('[GRID DEBUG] Triggering SilverStripe drag end:', { draggedElementId, insertAfterElementId });

  // Try multiple methods to trigger the drag end
  const elementList = document.querySelector('.elemental-editor-list');
  if (!elementList) {
    console.warn('[GRID DEBUG] Could not find elemental-editor-list');
    return;
  }

  // Method 1: Try React fiber (React 17/18)
  const fiberKey = Object.keys(elementList).find(key => key.startsWith('__reactInternalInstance') || key.startsWith('__reactFiber'));
  if (fiberKey && elementList[fiberKey]) {
    let reactComponent = elementList[fiberKey];
    let attempts = 0;
    while (reactComponent && attempts < 10) {
      if (reactComponent.memoizedProps && reactComponent.memoizedProps.onDragEnd) {
        console.log('[GRID DEBUG] Found React component with onDragEnd handler via fiber');
        reactComponent.memoizedProps.onDragEnd(draggedElementId, insertAfterElementId);
        return;
      }
      reactComponent = reactComponent.return || reactComponent.child;
      attempts++;
    }
  }

  // Method 2: Try to find React instance via properties
  if (elementList._reactInternalInstance) {
    let reactComponent = elementList._reactInternalInstance;
    let attempts = 0;
    while (reactComponent && attempts < 10) {
      if (reactComponent.props && reactComponent.props.onDragEnd) {
        console.log('[GRID DEBUG] Found React component with onDragEnd handler via instance');
        reactComponent.props.onDragEnd(draggedElementId, insertAfterElementId);
        return;
      }
      reactComponent = reactComponent._currentElement && reactComponent._currentElement._owner;
      attempts++;
    }
  }

  // Method 3: Fallback - try to simulate a hover bar click for the same effect
  console.log('[GRID DEBUG] React component access failed, falling back to hover bar simulation');
  const targetElement = document.querySelector(`[data-element-id="${insertAfterElementId}"]`) ||
                       document.querySelector('.element-editor__element');

  if (targetElement) {
    const elementWrapper = targetElement.parentElement;
    const hoverBar = elementWrapper && elementWrapper.nextElementSibling;
    if (hoverBar && hoverBar.classList.contains('element-editor__hover-bar')) {
      console.log('[GRID DEBUG] Triggering hover bar as fallback');
      triggerHoverBarClick(hoverBar);
    }
  }
};

// Helper function to get area ID from the context
const getAreaIdFromContext = (targetElement) => {
  // Try to find the area ID from the elemental editor list
  const elementalList = targetElement.closest('.elemental-editor-list');
  if (elementalList) {
    // Look for data attributes or React fiber props that contain area ID
    const areaIdAttribute = elementalList.getAttribute('data-area-id');
    if (areaIdAttribute) {
      return parseInt(areaIdAttribute, 10);
    }
  }

  // Fallback: try to extract from URL or other context
  const urlParams = new URLSearchParams(window.location.search);
  const areaIdFromUrl = urlParams.get('ElementalAreaID');
  if (areaIdFromUrl) {
    return parseInt(areaIdFromUrl, 10);
  }

  // Default fallback
  console.warn('[GRID DEBUG] Could not determine area ID, using default');
  return 1;
};

// Helper function to get allowed element types
const getAllowedElementTypes = () => {
  // Try to get element types from the window context if available
  if (window.ss && window.ss.elementTypes) {
    return window.ss.elementTypes;
  }

  // Fallback: return empty array, ReactGridDropZone will handle this
  return [];
};



// Helper function to trigger the appropriate hover bar based on position
const triggerHoverBarForPosition = (targetElement, position) => {
  const elementWrapper = targetElement.parentElement;
  if (!elementWrapper) return;

  let hoverBar = null;

  if (position === 'left' || position === 'above') {
    // For left/above positions, use the hover bar before this element
    hoverBar = elementWrapper.previousElementSibling;
  } else if (position === 'right' || position === 'below') {
    // For right/below positions, use the hover bar after this element
    hoverBar = elementWrapper.nextElementSibling;
  }

  if (hoverBar && hoverBar.classList.contains('element-editor__hover-bar')) {
    console.log('[GRID DEBUG] Triggering hover bar for', position, 'position');
    triggerHoverBarClick(hoverBar);
  } else {
    console.log('[GRID DEBUG] No hover bar found for', position, 'position');
  }
};

// Setup native drag and drop events to integrate with React DnD
const setupNativeDragDropEvents = (zone, targetElement, position) => {
  // Make the zone a proper drop target
  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    zone.classList.add('grid-drop-zone--drag-over');
  });

  zone.addEventListener('dragenter', (e) => {
    e.preventDefault();
    zone.classList.add('grid-drop-zone--drag-over');
  });

  zone.addEventListener('dragleave', (e) => {
    if (!zone.contains(e.relatedTarget)) {
      zone.classList.remove('grid-drop-zone--drag-over');
    }
  });

  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('grid-drop-zone--drag-over');

    // Debug: Log all available drag data types
    console.log('[GRID DEBUG] Drop event - available data types:', e.dataTransfer.types);

    // The issue is that SilverStripe stores data as application/json but it returns '[object Object]'
    // We need to get the actual drag data from the drag event or monitor
    let draggedElementId = null;

    // Try to get drag data from different sources
    const rawJsonData = e.dataTransfer.getData('application/json');
    console.log('[GRID DEBUG] Raw JSON data:', rawJsonData);

    // Since we can't get the actual data from dataTransfer, we need to intercept it from the drag start
    // Let's try to get it from the dragged element's attributes or global state
    const draggedElement = document.querySelector('.element-editor__element--dragging');
    if (draggedElement) {
      console.log('[GRID DEBUG] Currently dragging element attributes:', {
        tagName: draggedElement.tagName,
        className: draggedElement.className,
        id: draggedElement.id,
        attributes: Array.from(draggedElement.attributes).map(attr => `${attr.name}="${attr.value}"`)
      });

      const rawDraggedElementId = draggedElement.getAttribute('data-element-id') ||
                        draggedElement.getAttribute('data-id') ||
                        draggedElement.getAttribute('data-block-id') ||
                        draggedElement.getAttribute('data-element') ||
                        draggedElement.id;
      draggedElementId = extractNumericId(rawDraggedElementId);
      console.log('[GRID DEBUG] Found dragged element via CSS class:', rawDraggedElementId, '-> converted to numeric:', draggedElementId);

      // Try to find ID in child elements if main element doesn't have it
      if (!draggedElementId) {
        const childWithId = draggedElement.querySelector('[data-element-id], [data-id], [data-block-id], [id]');
        if (childWithId) {
          const rawChildElementId = childWithId.getAttribute('data-element-id') ||
                            childWithId.getAttribute('data-id') ||
                            childWithId.getAttribute('data-block-id') ||
                            childWithId.id;
          draggedElementId = extractNumericId(rawChildElementId);
          console.log('[GRID DEBUG] Found element ID in dragging child element:', rawChildElementId, '-> converted to numeric:', draggedElementId);
        }
      }
    }

    // If still no ID, try to find it from the drag image or any recently active element
    if (!draggedElementId) {
      // Look for any element that might have been recently dragged
      const allElements = document.querySelectorAll('.element-editor__element[data-element-id]');
      for (const element of allElements) {
        if (element.style.opacity === '0.5' || element.classList.contains('dragging')) {
          const rawElementId = element.getAttribute('data-element-id');
          draggedElementId = extractNumericId(rawElementId);
          console.log('[GRID DEBUG] Found dragged element via opacity/class:', rawElementId, '-> converted to numeric:', draggedElementId);
          break;
        }
      }
    }

    // Alternative approach: Check if there's a global drag state we can access
    if (!draggedElementId && window.currentDraggedElement) {
      draggedElementId = window.currentDraggedElement;
      // Ensure the ID is numeric (fallback in case it wasn't converted earlier)
      if (draggedElementId && !/^\d+$/.test(draggedElementId)) {
        draggedElementId = extractNumericId(draggedElementId);
        console.log('[GRID DEBUG] Found dragged element from global state and converted to numeric:', draggedElementId);
      } else {
        console.log('[GRID DEBUG] Found dragged element from global state (already numeric):', draggedElementId);
      }
    }

    if (!draggedElementId) {
      console.warn('[GRID DEBUG] Could not determine dragged element ID. Available data:', {
        hasDataTransfer: !!e.dataTransfer,
        types: e.dataTransfer.types,
        effectAllowed: e.dataTransfer.effectAllowed,
        dropEffect: e.dataTransfer.dropEffect,
        rawJsonData
      });
      // Don't return - let's try the fallback approach
    } else {
      console.log('[GRID DEBUG] Successfully found dragged element ID:', draggedElementId);
    }

    // Calculate insertion position
    const insertionData = calculateGridInsertionPosition(position, targetElement);
    console.log('[GRID DEBUG] Native drop on', position, 'zone:', insertionData);

    // If we have a valid element ID, trigger the drag end handler
    if (draggedElementId) {
      console.log('[GRID DEBUG] Triggering drag end with element ID:', draggedElementId);
      triggerSilverStripeDragEnd(draggedElementId, insertionData.insertAfterElementId);
    } else {
      // Fallback: Try to trigger hover bar functionality as a last resort
      console.log('[GRID DEBUG] No element ID - falling back to hover bar trigger');
      triggerHoverBarForPosition(targetElement, position);
    }
  });
};

window.document.addEventListener('DOMContentLoaded', () => {
  console.log('[GRID DEBUG] DOMContentLoaded - Starting alongside grid enhancements...');

  // Keep the Element enhancement (this works well)
  console.log('[GRID DEBUG] Applying Element enhancement...');
  Injector.transform('grid-element-enhancement', (updater) => {
    updater.component('Element', withGridFunctionality);
    console.log('[GRID DEBUG] Element enhanced with grid functionality');
  });

  // Keep the toolbar enhancement (this works well)
  console.log('[GRID DEBUG] Applying ElementToolbar enhancement...');
  Injector.transform('elemental-grid-toolbar', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
    console.log('[GRID DEBUG] ElementToolbar enhanced');
  });

  // Set up drag event listeners (keep existing functionality)
  addDragEventListeners();

  // Set up DOM manipulation to move controls inside cards
  setTimeout(() => {
    moveGridControlsIntoCards();

    // Inject our grid drop zones alongside existing system
    injectGridDropZones();

    console.log('[GRID DEBUG] Grid enhancements applied alongside existing system');

    // Enhanced observer to detect React re-renders and maintain grid enhancements
    const observer = new MutationObserver((mutations) => {
      let shouldReapply = false;
      let shouldRestoreRowStyling = false;
      let shouldReenhanceSystem = false;

      mutations.forEach(mutation => {
        // Check for added/removed nodes (new elements or React re-renders)
        if (mutation.type === 'childList' && (mutation.addedNodes.length || mutation.removedNodes.length)) {
          // Check if this is during a drag operation
          if (window.isDraggingElement) {
            shouldRestoreRowStyling = true;
          }
          shouldReapply = true;

          // Check if new elements were added that need grid enhancements
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              if (node.classList && node.classList.contains('element-editor__element')) {
                shouldReenhanceSystem = true;
                console.log('[GRID DEBUG] New element detected, will re-enhance system');
              }
            }
          });
        }

        // Check for class changes that might indicate drag operations or React re-renders
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          const { target } = mutation;

          // Check if a wrapper div (direct child of .elemental-editor-list) lost its grid classes
          if (target.parentElement && target.parentElement.classList.contains('elemental-editor-list')) {
            const hasAnyGridClass = target.classList.contains('col-lg-1') ||
                                   target.classList.contains('col-lg-2') ||
                                   target.classList.contains('col-lg-3') ||
                                   target.classList.contains('col-lg-4') ||
                                   target.classList.contains('col-lg-5') ||
                                   target.classList.contains('col-lg-6') ||
                                   target.classList.contains('col-lg-7') ||
                                   target.classList.contains('col-lg-8') ||
                                   target.classList.contains('col-lg-9') ||
                                   target.classList.contains('col-lg-10') ||
                                   target.classList.contains('col-lg-11') ||
                                   target.classList.contains('col-lg-12');

            if (!hasAnyGridClass) {
              shouldReapply = true;

              if (window.isDraggingElement) {
                shouldRestoreRowStyling = true;
              }
            }
          }

          // Check if an element card lost the is-row class during drag operations
          if (target.classList.contains('element-editor__element')) {
            const titleElement = target.querySelector('.element-editor-header__title');
            const hasGridControls = target.querySelector('.column-size-controls');
            const shouldBeRow = !hasGridControls ||
                               (titleElement && titleElement.textContent.includes('Row block'));

            if (shouldBeRow && !target.classList.contains('is-row')) {
              shouldRestoreRowStyling = true;

              if (window.isDraggingElement) {
                // Immediate restoration during drag
                target.classList.add('is-row');
              }
            }
          }

          // Detect React component re-renders during drag by checking for fresh DOM nodes
          if (window.isDraggingElement && target.parentElement && target.parentElement.classList.contains('elemental-editor-list')) {
            // If an element wrapper gets fresh className during drag, it might be a React re-render
            if (!target.hasAttribute('data-grid-processed')) {
              shouldRestoreRowStyling = true;
              shouldReapply = true;
            }
          }
        }
      });

      // Prioritize row styling restoration during drag operations
      if (shouldRestoreRowStyling && window.isDraggingElement) {
        queueMicrotask(() => {
          restoreRowElementStyling();
        });
      }

      if (shouldReapply) {
        // Use different timing based on whether we're in a drag operation
        if (window.isDraggingElement) {
          // Faster response during drag
          queueMicrotask(() => {
            throttledMoveGridControls();
          });
        } else {
          // Normal response outside drag
          queueMicrotask(() => {
            moveGridControlsIntoCards();
          });
        }
      }

      // Re-enhance the system when new elements are added
      if (shouldReenhanceSystem) {
        setTimeout(() => {
          console.log('[GRID DEBUG] Re-enhancing system due to new content...');
          injectGridDropZones();
        }, 100);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class']
    });
  }, 1000);
});
