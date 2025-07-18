import Injector from 'lib/Injector';
import React from 'react';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

// Register our grid components at module load time
Injector.component.registerMany({
  AddBlockToBottomButton,
  AddBlockToTopButton,
  ColumnSize,
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

      // Re-apply grid classes immediately after drag operation completes
      // Use requestAnimationFrame to ensure DOM is updated but avoid visual shifts
      requestAnimationFrame(() => {
        moveGridControlsIntoCards();
      });
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

      // Re-apply grid classes immediately using requestAnimationFrame
      requestAnimationFrame(() => {
        moveGridControlsIntoCards();
      });

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

window.document.addEventListener('DOMContentLoaded', () => {
  // Use Injector.transform() to enhance the Element component instead of replacing it
  Injector.transform('grid-element-enhancement', (updater) => {
    updater.component('Element', withGridFunctionality);
  });

  // Register the toolbar that uses the buttons
  Injector.transform('elemental-grid-toolbar', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
  });

  // Set up drag event listeners
  addDragEventListeners();

  // Set up DOM manipulation to move controls inside cards
  setTimeout(() => {
    moveGridControlsIntoCards();

    // Enhanced observer to detect React re-renders and styling loss during drag operations
    const observer = new MutationObserver((mutations) => {
      let shouldReapply = false;
      let shouldRestoreRowStyling = false;

      mutations.forEach(mutation => {
        // Check for added/removed nodes (new elements or React re-renders)
        if (mutation.type === 'childList' && (mutation.addedNodes.length || mutation.removedNodes.length)) {
          // Check if this is during a drag operation
          if (window.isDraggingElement) {
            shouldRestoreRowStyling = true;
          }
          shouldReapply = true;
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
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class']
    });
  }, 1000);
});
