import { inject } from 'lib/Injector';
import i18n from 'i18n';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import classNames from 'classnames';

/**
 * GridDropZone provides larger, more intuitive drop zones for grid layouts.
 * Supports left/right placement within rows and provides visual feedback.
 */
class GridDropZone extends Component {
  constructor(props) {
    super(props);
    this.state = {
      popoverOpen: false
    };
    this.toggle = this.toggle.bind(this);
  }

  toggle() {
    this.setState((prevState) => ({
      popoverOpen: !prevState.popoverOpen
    }));
  }

  render() {
    const {
      AddElementPopoverComponent,
      elementTypes,
      areaId,
      insertAfterElement,
      position,
      isOver,
      connectDropTarget,
      className
    } = this.props;

    console.log('[GRID DEBUG] GridDropZone render:', {
      insertAfterElement,
      position,
      isOver,
      areaId,
      elementTypesCount: (elementTypes && elementTypes.length) || 0
    });

    const { popoverOpen } = this.state;

    // Determine the zone type and styling
    const isLeftZone = position === 'left';
    const isRightZone = position === 'right';
    const isAboveZone = position === 'above';
    const isBelowZone = position === 'below';

    const zoneClassNames = classNames(
      'grid-drop-zone',
      {
        'grid-drop-zone--left': isLeftZone,
        'grid-drop-zone--right': isRightZone,
        'grid-drop-zone--above': isAboveZone,
        'grid-drop-zone--below': isBelowZone,
        'grid-drop-zone--hover': isOver,
        'grid-drop-zone--active': popoverOpen,
      },
      className
    );

    // Button props for the drop zone
    const buttonId = `GridDropZone_${areaId}_${insertAfterElement}_${position}`;
    const label = isLeftZone ? i18n._t('GridDropZone.ADD_LEFT', 'Add block to the left') :
      isRightZone ? i18n._t('GridDropZone.ADD_RIGHT', 'Add block to the right') :
        isAboveZone ? i18n._t('GridDropZone.ADD_ABOVE', 'Add block above') :
          i18n._t('GridDropZone.ADD_BELOW', 'Add block below');

    const btnProps = {
      className: 'grid-drop-zone__button',
      onClick: this.toggle,
      'aria-label': label,
      title: label,
      id: buttonId
    };

    return connectDropTarget(
      <div className={zoneClassNames} id={`${buttonId}_Container`}>
        <div className="grid-drop-zone__inner">
          <button {...btnProps}>
            <span className="grid-drop-zone__icon font-icon-plus-circled" />
            <span className="grid-drop-zone__label">{label}</span>
          </button>

          {AddElementPopoverComponent && (
            <AddElementPopoverComponent
              placement={isAboveZone || isBelowZone ? 'bottom' : 'right'}
              target={buttonId}
              isOpen={popoverOpen}
              elementTypes={elementTypes}
              toggle={this.toggle}
              container={`#${buttonId}_Container`}
              areaId={areaId}
              insertAfterElement={insertAfterElement}
              position={position}
            />
          )}
        </div>
      </div>
    );
  }
}

GridDropZone.propTypes = {
  elementTypes: PropTypes.array.isRequired,
  areaId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  insertAfterElement: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  position: PropTypes.oneOf(['left', 'right', 'above', 'below']).isRequired,
  isOver: PropTypes.bool,
  connectDropTarget: PropTypes.func.isRequired,
  className: PropTypes.string,
  AddElementPopoverComponent: PropTypes.func,
};

GridDropZone.defaultProps = {
  isOver: false,
  className: '',
  AddElementPopoverComponent: null,
};

export { GridDropZone as Component };

// Drop target configuration for grid-aware dropping
const gridDropTarget = {
  drop(props, monitor, component) {
    const { insertAfterElement, position } = props;
    const draggedItem = monitor.getItem();

    return {
      target: insertAfterElement,
      gridPosition: position,
      dropSpot: position === 'above' || position === 'left' ? 'top' : 'bottom',
      draggedItem,
    };
  },

  hover(props, monitor, component) {
    // Optional: implement hover feedback if needed
  },

  canDrop(props, monitor) {
    // Ensure we can only drop elements, not other types
    return monitor.getItemType() === 'element';
  }
};

export default inject(
  ['AddElementPopover'],
  (AddElementPopoverComponent) => ({
    AddElementPopoverComponent,
  }),
  () => 'GridDropZone'
)(
  DropTarget('element', gridDropTarget, (connector, monitor) => ({
    connectDropTarget: connector.dropTarget(),
    isOver: monitor.isOver(),
    canDrop: monitor.canDrop(),
  }))(GridDropZone)
);
