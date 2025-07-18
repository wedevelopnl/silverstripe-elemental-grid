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

    // Render the original element
    const originalElement = React.createElement(OriginalElement, props);

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
  }
  
  // Remove grid classes from wrapper divs (should be on element cards instead)
  const wrapperDivs = document.querySelectorAll('.elemental-editor-list > div');
  wrapperDivs.forEach(div => {
    if (div.classList.contains('col-lg-1') || div.classList.contains('col-lg-2') || 
        div.classList.contains('col-lg-3') || div.classList.contains('col-lg-4') || 
        div.classList.contains('col-lg-5') || div.classList.contains('col-lg-6') || 
        div.classList.contains('col-lg-7') || div.classList.contains('col-lg-8') || 
        div.classList.contains('col-lg-9') || div.classList.contains('col-lg-10') || 
        div.classList.contains('col-lg-11') || div.classList.contains('col-lg-12')) {
      div.className = div.className.replace(/\bcol-lg-\d+\b/g, '');
      div.className = div.className.replace(/\boffset-lg-\d+\b/g, '');
    }
  });
};

// Function to move grid controls into their respective cards
const moveGridControlsIntoCards = () => {
  // First clean up any incorrectly applied grid classes
  cleanupIncorrectGridClasses();
  
  const gridControls = document.querySelectorAll('.column-size-controls');
  
  gridControls.forEach(control => {
    // Find the element ID from the control inputs
    const sizeSelect = control.querySelector('[id^="columnSize-"]');
    const offsetSelect = control.querySelector('[id^="columnOffset-"]');
    if (!sizeSelect || !offsetSelect) {
      return;
    }
    
    // The control is rendered as a sibling to the element card
    // Look for the element card that's a sibling to this control
    const parent = control.parentElement;
    if (!parent) return;
    
    const elementCard = Array.from(parent.children).find(child => 
      child.classList.contains('element-editor__element')
    );
    
    if (!elementCard) return;
    
    // Check if already moved
    if (elementCard.contains(control)) {
      return;
    }
    
    // Move the control into the card
    elementCard.appendChild(control);
    
    // Apply grid classes directly to the element card itself to prevent drag handle positioning issues
    applyGridClasses(elementCard, sizeSelect.value, offsetSelect.value);
    
    // Listen for changes to the dropdowns and update classes
    if (!sizeSelect.hasAttribute('data-grid-listener')) {
      sizeSelect.setAttribute('data-grid-listener', 'true');
      sizeSelect.addEventListener('change', (e) => {
        applyGridClasses(elementCard, e.target.value, offsetSelect.value);
      });
    }
    
    if (!offsetSelect.hasAttribute('data-grid-listener')) {
      offsetSelect.setAttribute('data-grid-listener', 'true');
      offsetSelect.addEventListener('change', (e) => {
        applyGridClasses(elementCard, sizeSelect.value, e.target.value);
      });
    }
  });
};

// Function to apply Bootstrap grid classes to an element holder
const applyGridClasses = (elementHolder, size, offset) => {
  // Remove existing grid classes
  elementHolder.className = elementHolder.className.replace(/\bcol-lg-\d+\b/g, '');
  elementHolder.className = elementHolder.className.replace(/\boffset-lg-\d+\b/g, '');
  
  // Add new grid classes
  elementHolder.classList.add(`col-lg-${size}`);
  
  if (offset && offset > 0) {
    elementHolder.classList.add(`offset-lg-${offset}`);
  }
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

  // Set up DOM manipulation to move controls inside cards
  setTimeout(() => {
    moveGridControlsIntoCards();
    
    // Watch for new controls being added
    const observer = new MutationObserver(() => {
      moveGridControlsIntoCards();
    });
    
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }, 1000);
});
