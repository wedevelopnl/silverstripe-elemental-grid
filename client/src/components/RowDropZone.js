import { inject } from 'lib/Injector';
import i18n from 'i18n';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import classNames from 'classnames';

/**
 * RowDropZone provides full-width drop zones for row-level operations.
 * Handles above/below placement for creating new rows or inserting between rows.
 */
class RowDropZone extends Component {
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
      className,
      isFirstRow,
      isLastRow
    } = this.props;

    console.log('[GRID DEBUG] RowDropZone render:', {
      insertAfterElement,
      position,
      isOver,
      areaId,
      isFirstRow,
      isLastRow,
      elementTypesCount: (elementTypes && elementTypes.length) || 0
    });

    const { popoverOpen } = this.state;

    // Determine the zone type and styling
    const isAbove = position === 'above';
    const isBelow = position === 'below';

    const zoneClassNames = classNames(
      'row-drop-zone',
      {
        'row-drop-zone--above': isAbove,
        'row-drop-zone--below': isBelow,
        'row-drop-zone--first': isFirstRow,
        'row-drop-zone--last': isLastRow,
        'row-drop-zone--hover': isOver,
        'row-drop-zone--active': popoverOpen,
      },
      className
    );

    // Button props for the drop zone
    const buttonId = `RowDropZone_${areaId}_${insertAfterElement}_${position}`;
    const label = isAbove ?
      i18n._t('RowDropZone.ADD_ROW_ABOVE', 'Add new row above') :
      i18n._t('RowDropZone.ADD_ROW_BELOW', 'Add new row below');

    const btnProps = {
      className: 'row-drop-zone__button',
      onClick: this.toggle,
      'aria-label': label,
      title: label,
      id: buttonId
    };

    return connectDropTarget(
      <div className={zoneClassNames} id={`${buttonId}_Container`}>
        <div className="row-drop-zone__inner">
          {/* Visual indicator line */}
          <div className="row-drop-zone__line" />

          {/* Add button */}
          <button {...btnProps}>
            <span className="row-drop-zone__icon font-icon-plus-circled" />
            <span className="row-drop-zone__label">{label}</span>
          </button>

          {AddElementPopoverComponent && (
            <AddElementPopoverComponent
              placement="bottom"
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

RowDropZone.propTypes = {
  elementTypes: PropTypes.array.isRequired,
  areaId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  insertAfterElement: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  position: PropTypes.oneOf(['above', 'below']).isRequired,
  isOver: PropTypes.bool,
  connectDropTarget: PropTypes.func.isRequired,
  className: PropTypes.string,
  isFirstRow: PropTypes.bool,
  isLastRow: PropTypes.bool,
  AddElementPopoverComponent: PropTypes.func,
};

RowDropZone.defaultProps = {
  isOver: false,
  className: '',
  isFirstRow: false,
  isLastRow: false,
  AddElementPopoverComponent: null,
};

export { RowDropZone as Component };

// Drop target configuration for row-level dropping
const rowDropTarget = {
  drop(props, monitor, component) {
    const { insertAfterElement, position } = props;
    const draggedItem = monitor.getItem();

    return {
      target: insertAfterElement,
      rowPosition: position,
      dropSpot: position === 'above' ? 'top' : 'bottom',
      draggedItem,
      isRowDrop: true, // Flag to indicate this is a row-level drop
    };
  },

  hover(props, monitor, component) {
    // Optional: implement row-specific hover feedback
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
  () => 'RowDropZone'
)(
  DropTarget('element', rowDropTarget, (connector, monitor) => ({
    connectDropTarget: connector.dropTarget(),
    isOver: monitor.isOver(),
    canDrop: monitor.canDrop(),
  }))(RowDropZone)
);
