import { inject } from 'lib/Injector';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import classNames from 'classnames';

/**
 * ReactGridDropZone - Proper React DnD DropTarget for grid positioning
 * Handles both clicking and drag/drop operations for grid-aware element placement
 */
class ReactGridDropZone extends Component {
  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  handleClick(e) {
    e.preventDefault();
    console.log('[GRID DEBUG] React grid drop zone clicked:', this.props.position);

    // Find the appropriate hover bar to trigger for click operations
    this.triggerHoverBarClick();
  }

  triggerHoverBarClick() {
    const { targetElement, position } = this.props;
    const elementWrapper = targetElement.parentElement;

    if (elementWrapper) {
      let hoverBar = null;

      if (position === 'left' || position === 'above') {
        // For left/above positions, use the hover bar before this element
        hoverBar = elementWrapper.previousElementSibling;
      } else if (position === 'right' || position === 'below') {
        // For right/below positions, use the hover bar after this element
        hoverBar = elementWrapper.nextElementSibling;
      }

      if (hoverBar && hoverBar.classList.contains('element-editor__hover-bar')) {
        const hoverButton = hoverBar.querySelector('.element-editor__hover-bar-area');
        if (hoverButton) {
          console.log('[GRID DEBUG] Triggering hover bar click for', position);
          hoverButton.click();
        }
      }
    }
  }

  render() {
    const {
      position,
      isOver,
      canDrop,
      connectDropTarget,
      targetElement,
      AddElementPopoverComponent,
      elementTypes,
      areaId
    } = this.props;

    console.log('[GRID DEBUG] ReactGridDropZone render:', {
      position,
      isOver,
      canDrop,
      targetElementId: (targetElement && targetElement.getAttribute && targetElement.getAttribute('data-element-id')) || 'unknown'
    });

    const isGridZone = position === 'left' || position === 'right';
    const isRowZone = position === 'above' || position === 'below';

    const zoneClasses = classNames({
      'grid-drop-zone': isGridZone,
      'row-drop-zone': isRowZone,
      [`${isGridZone ? 'grid' : 'row'}-drop-zone--${position}`]: true,
      [`${isGridZone ? 'grid' : 'row'}-drop-zone--hover`]: isOver,
      [`${isGridZone ? 'grid' : 'row'}-drop-zone--can-drop`]: canDrop,
    });

    const content = isGridZone ? this.renderGridZone() : this.renderRowZone();

    return connectDropTarget(
      <div className={zoneClasses} onClick={this.handleClick}>
        {content}
      </div>
    );
  }

  renderGridZone() {
    const { position } = this.props;

    return (
      <div className="grid-drop-zone__inner">
        <button className="grid-drop-zone__button" title={`Add block ${position}`}>
          <span className="grid-drop-zone__icon font-icon-plus-circled" />
          <span className="grid-drop-zone__label">Add {position}</span>
        </button>
      </div>
    );
  }

  renderRowZone() {
    const { position } = this.props;

    return (
      <div className="row-drop-zone__inner">
        <div className="row-drop-zone__line" />
        <button className="row-drop-zone__button" title={`Add row ${position}`}>
          <span className="row-drop-zone__icon font-icon-plus-circled" />
          <span className="row-drop-zone__label">Add row {position}</span>
        </button>
      </div>
    );
  }
}

ReactGridDropZone.propTypes = {
  position: PropTypes.oneOf(['left', 'right', 'above', 'below']).isRequired,
  targetElement: PropTypes.object.isRequired,
  elementTypes: PropTypes.array,
  areaId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  // React DnD props
  isOver: PropTypes.bool.isRequired,
  canDrop: PropTypes.bool.isRequired,
  connectDropTarget: PropTypes.func.isRequired,
  // Injected props
  AddElementPopoverComponent: PropTypes.func,
};

ReactGridDropZone.defaultProps = {
  elementTypes: [],
  AddElementPopoverComponent: null,
};

export { ReactGridDropZone as Component };

// Drop target specification for React DnD
const gridDropTarget = {
  drop(props, monitor) {
    const { position, targetElement, areaId } = props;
    const draggedItem = monitor.getItem();

    console.log('[GRID DEBUG] ReactGridDropZone drop event:', {
      position,
      draggedItemId: draggedItem && draggedItem.id,
      targetElementId: (targetElement && targetElement.getAttribute && targetElement.getAttribute('data-element-id')) || 'unknown',
      areaId
    });

    // Calculate the correct insertion position based on grid placement
    const insertionResult = calculateGridInsertionPosition(position, targetElement, draggedItem);

    console.log('[GRID DEBUG] Calculated insertion:', insertionResult);

    return {
      target: insertionResult.insertAfterElementId,
      dropSpot: insertionResult.dropSpot,
      gridPosition: position,
      draggedItem,
    };
  },

  hover(props, monitor) {
    // Optional: Could add hover feedback here
    console.log('[GRID DEBUG] Hovering over grid drop zone:', props.position);
  },

  canDrop(props, monitor) {
    // Ensure we can only drop elements
    const itemType = monitor.getItemType();
    const canDrop = itemType === 'element';
    console.log('[GRID DEBUG] Can drop check:', { itemType, canDrop });
    return canDrop;
  }
};

// Calculate the correct insertion position for grid placement
function calculateGridInsertionPosition(position, targetElement, draggedItem) {
  const targetElementId = targetElement.getAttribute('data-element-id');
  const elementWrapper = targetElement.parentElement;

  console.log('[GRID DEBUG] Calculating insertion for position:', position, 'target:', targetElementId);

  if (!elementWrapper) {
    console.warn('[GRID DEBUG] No element wrapper found');
    return { insertAfterElementId: '0', dropSpot: 'bottom' };
  }

  switch (position) {
    case 'left':
      // Insert before this element (same row, left position)
      const prevElement = elementWrapper.previousElementSibling;
      if (prevElement && prevElement.querySelector('.element-editor__element')) {
        const prevElementId = prevElement.querySelector('.element-editor__element').getAttribute('data-element-id');
        return { insertAfterElementId: prevElementId || '0', dropSpot: 'bottom' };
      }
      return { insertAfterElementId: '0', dropSpot: 'bottom' };

    case 'right':
      // Insert after this element (same row, right position)
      return { insertAfterElementId: targetElementId, dropSpot: 'bottom' };

    case 'above':
      // Insert before this element (new row above)
      const prevElementAbove = elementWrapper.previousElementSibling;
      if (prevElementAbove && prevElementAbove.querySelector('.element-editor__element')) {
        const prevElementId = prevElementAbove.querySelector('.element-editor__element').getAttribute('data-element-id');
        return { insertAfterElementId: prevElementId || '0', dropSpot: 'bottom' };
      }
      return { insertAfterElementId: '0', dropSpot: 'bottom' };

    case 'below':
      // Insert after this element (new row below)
      return { insertAfterElementId: targetElementId, dropSpot: 'bottom' };

    default:
      console.warn('[GRID DEBUG] Unknown position:', position);
      return { insertAfterElementId: targetElementId, dropSpot: 'bottom' };
  }
}

// Export with React DnD DropTarget HOC and Injector
export default inject(
  ['AddElementPopover'],
  (AddElementPopoverComponent) => ({
    AddElementPopoverComponent,
  }),
  () => 'ReactGridDropZone'
)(
  DropTarget('element', gridDropTarget, (connect, monitor) => ({
    connectDropTarget: connect.dropTarget(),
    isOver: monitor.isOver(),
    canDrop: monitor.canDrop(),
  }))(ReactGridDropZone)
);
