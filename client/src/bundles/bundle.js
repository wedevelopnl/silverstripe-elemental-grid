import React from 'react';
import Injector from 'lib/Injector';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

// Register grid Element component in DOMContentLoaded instead of at module load time
// This should ensure proper timing for the transform

// Register our grid components at module load time
Injector.component.registerMany({
  AddBlockToBottomButton,
  AddBlockToTopButton,
  ColumnSize,
});

// Create a higher-order component that enhances the existing Element with grid functionality
const withGridFunctionality = (OriginalElement) => {
  const GridEnhancedElement = (props) => {
    // Get the ColumnSize component from Injector
    const ColumnSizeComponent = Injector.component.get('ColumnSize');

    // Check if this element needs grid functionality
    const { element } = props;
    const hasGridSchema = element && element.blockSchema && element.blockSchema.grid;
    const isNotRow = hasGridSchema && !element.blockSchema.grid.isRow;

    // Hook into drag lifecycle to re-apply grid classes
    const originalOnDragEnd = props.onDragEnd;
    const enhancedOnDragEnd = React.useCallback((itemID, dropAfterID) => {
      console.log('🔧 Grid: Enhanced onDragEnd called with:', { itemID, dropAfterID });
      
      // Call the original onDragEnd first with the correct parameters
      if (originalOnDragEnd) {
        originalOnDragEnd(itemID, dropAfterID);
      }
      
      // Re-apply grid classes immediately after drag operation completes
      // Use requestAnimationFrame to ensure DOM is updated but avoid visual shifts
      requestAnimationFrame(() => {
        console.log('🔧 Grid: Re-applying grid classes after drag...');
        moveGridControlsIntoCards();
      });
    }, [originalOnDragEnd]);

    // Create enhanced props with our drag end handler
    const enhancedProps = {
      ...props,
      onDragEnd: enhancedOnDragEnd
    };

    // Render the original element with enhanced props
    const originalElement = React.createElement(OriginalElement, enhancedProps);

    // Post-render layout effect to ensure grid classes are applied before browser paint
    React.useLayoutEffect(() => {
      if (isNotRow && ColumnSizeComponent) {
        // Apply immediately before browser paint to prevent visual shifts
        moveGridControlsIntoCards();
      }
    }, [element.id, isNotRow, ColumnSizeComponent]);

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
      console.log('🔧 Grid: Removed grid classes from element card');
    }
    if (elementCard.className.match(/\boffset-lg-\d+\b/)) {
      elementCard.className = elementCard.className.replace(/\boffset-lg-\d+\b/g, '');
      console.log('🔧 Grid: Removed offset classes from element card');
    }
    elementCard.classList.remove('px-0'); // Remove px-0 from element cards
  });
};

// Function to move grid controls into their respective cards
const moveGridControlsIntoCards = () => {
  console.log('🔧 Grid: moveGridControlsIntoCards() called');
  
  // First clean up any incorrectly applied grid classes
  cleanupIncorrectGridClasses();
  
  // Add row class to the elemental-editor-list container to enable Bootstrap flexbox grid
  const elementalEditorList = document.querySelector('.elemental-editor-list');
  if (elementalEditorList && !elementalEditorList.classList.contains('row')) {
    elementalEditorList.classList.add('row');
    console.log('🔧 Grid: Added row class to elemental-editor-list container');
  }
  
  const gridControls = document.querySelectorAll('.column-size-controls');
  console.log('🔧 Grid: Found', gridControls.length, 'grid controls');
  
  gridControls.forEach((control, index) => {
    console.log('🔧 Grid: Processing control', index);
    
    // Find the element ID from the control inputs
    const sizeSelect = control.querySelector('[id^="columnSize-"]');
    const offsetSelect = control.querySelector('[id^="columnOffset-"]');
    if (!sizeSelect || !offsetSelect) {
      console.log('🔧 Grid: No size or offset select found for control', index);
      return;
    }
    
    console.log('🔧 Grid: Control', index, 'has size:', sizeSelect.value, 'offset:', offsetSelect.value);
    
    // Check if control is already inside an element card
    const existingElementCard = control.closest('.element-editor__element');
    if (existingElementCard) {
      console.log('🔧 Grid: Control', index, 'already inside element card, applying classes to wrapper');
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
      console.log('🔧 Grid: No parent found for control', index);
      return;
    }
    
    const elementCard = Array.from(parent.children).find(child => 
      child.classList.contains('element-editor__element')
    );
    
    if (!elementCard) {
      console.log('🔧 Grid: No element card found for control', index);
      return;
    }
    
    console.log('🔧 Grid: Found element card for control', index, 'classes:', elementCard.className);
    console.log('🔧 Grid: Moving control', index, 'into element card');
    
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
        console.log('🔧 Grid: Size changed to', e.target.value);
        if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
          applyGridClassesToWrapper(wrapperDiv, e.target.value, offsetSelect.value);
        }
      });
    }
    
    if (!offsetSelect.hasAttribute('data-grid-listener')) {
      offsetSelect.setAttribute('data-grid-listener', 'true');
      offsetSelect.addEventListener('change', (e) => {
        console.log('🔧 Grid: Offset changed to', e.target.value);
        if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
          applyGridClassesToWrapper(wrapperDiv, sizeSelect.value, e.target.value);
        }
      });
    }
  });
  
  // Identify and handle row elements (elements without grid controls)
  const allElementCards = document.querySelectorAll('.element-editor__element');
  allElementCards.forEach((elementCard, index) => {
    const hasGridControls = elementCard.querySelector('.column-size-controls');
    const titleElement = elementCard.querySelector('.element-editor-header__title');
    const isRowElement = !hasGridControls || 
                         (titleElement && titleElement.textContent.includes('Row block'));
    
    if (isRowElement) {
      console.log('🔧 Grid: Found row element', index, 'title:', titleElement ? titleElement.textContent : 'No title');
      
      // Add is-row class for identification to the element card
      if (!elementCard.classList.contains('is-row')) {
        elementCard.classList.add('is-row');
        console.log('🔧 Grid: Added is-row class to element', index);
      }
      
      // Force row elements to be full-width breaks by applying classes to wrapper
      const wrapperDiv = elementCard.parentElement;
      if (wrapperDiv && wrapperDiv.parentElement && wrapperDiv.parentElement.classList.contains('elemental-editor-list')) {
        applyGridClassesToWrapper(wrapperDiv, 12, 0);
        console.log('🔧 Grid: Applied full-width classes to row element wrapper', index);
      }
    }
  });
};

// Function to apply Bootstrap grid classes to an element holder (legacy compatibility)
const applyGridClasses = (elementHolder, size, offset) => {
  console.log('🔧 Grid: applyGridClasses called (legacy) with:', { size, offset, element: elementHolder });
  console.log('🔧 Grid: Current classes before:', elementHolder.className);
  
  // Remove existing grid classes
  elementHolder.className = elementHolder.className.replace(/\bcol-lg-\d+\b/g, '');
  elementHolder.className = elementHolder.className.replace(/\boffset-lg-\d+\b/g, '');
  
  console.log('🔧 Grid: Classes after removal:', elementHolder.className);
  
  // Add new grid classes
  elementHolder.classList.add(`col-lg-${size}`);
  console.log('🔧 Grid: Added col-lg-' + size);
  
  if (offset && offset > 0) {
    elementHolder.classList.add(`offset-lg-${offset}`);
    console.log('🔧 Grid: Added offset-lg-' + offset);
  }
  
  console.log('🔧 Grid: Final classes:', elementHolder.className);
};

// Function to apply Bootstrap grid classes to wrapper divs (correct Bootstrap implementation)
const applyGridClassesToWrapper = (wrapperDiv, size, offset) => {
  console.log('🔧 Grid: applyGridClassesToWrapper called with:', { size, offset, wrapper: wrapperDiv });
  console.log('🔧 Grid: Current wrapper classes before:', wrapperDiv.className);
  
  // Remove existing grid classes from wrapper
  wrapperDiv.className = wrapperDiv.className.replace(/\bcol-lg-\d+\b/g, '');
  wrapperDiv.className = wrapperDiv.className.replace(/\boffset-lg-\d+\b/g, '');
  
  console.log('🔧 Grid: Wrapper classes after removal:', wrapperDiv.className);
  
  // Add new grid classes to wrapper (direct child of .row)
  wrapperDiv.classList.add(`col-lg-${size}`);
  console.log('🔧 Grid: Added col-lg-' + size + ' to wrapper');
  
  if (offset && offset > 0) {
    wrapperDiv.classList.add(`offset-lg-${offset}`);
    console.log('🔧 Grid: Added offset-lg-' + offset + ' to wrapper');
  }
  
  // Add Bootstrap no horizontal padding class to prevent padding conflicts
  if (!wrapperDiv.classList.contains('px-0')) {
    wrapperDiv.classList.add('px-0');
    console.log('🔧 Grid: Added px-0 class to wrapper for proper element editor spacing');
  }
  
  console.log('🔧 Grid: Final wrapper classes:', wrapperDiv.className);
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
    
    // Watch for new controls being added AND class changes (drag operations)
    const observer = new MutationObserver((mutations) => {
      let shouldReapply = false;
      
      mutations.forEach(mutation => {
        // Check for added/removed nodes (new elements)
        if (mutation.type === 'childList' && (mutation.addedNodes.length || mutation.removedNodes.length)) {
          shouldReapply = true;
        }
        
        // Check for class changes that might indicate drag operations
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          const target = mutation.target;
          // If a wrapper div (direct child of .elemental-editor-list) lost its grid classes, we need to reapply
          if (target.parentElement && target.parentElement.classList.contains('elemental-editor-list') && 
              !target.classList.contains('col-lg-1') && 
              !target.classList.contains('col-lg-2') && 
              !target.classList.contains('col-lg-3') && 
              !target.classList.contains('col-lg-4') && 
              !target.classList.contains('col-lg-5') && 
              !target.classList.contains('col-lg-6') && 
              !target.classList.contains('col-lg-7') && 
              !target.classList.contains('col-lg-8') && 
              !target.classList.contains('col-lg-9') && 
              !target.classList.contains('col-lg-10') && 
              !target.classList.contains('col-lg-11') && 
              !target.classList.contains('col-lg-12')) {
            shouldReapply = true;
          }
        }
      });
      
      if (shouldReapply) {
        // Apply immediately using queueMicrotask for non-blocking updates
        queueMicrotask(() => {
          moveGridControlsIntoCards();
        });
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
