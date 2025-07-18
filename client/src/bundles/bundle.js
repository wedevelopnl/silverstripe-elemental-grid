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

window.document.addEventListener('DOMContentLoaded', () => {
  // Use Injector.transform() to enhance the Element component instead of replacing it
  Injector.transform('grid-element-enhancement', (updater) => {
    updater.component('Element', withGridFunctionality);
  });

  // Register the toolbar that uses the buttons
  Injector.transform('elemental-grid-toolbar', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
  });
});
