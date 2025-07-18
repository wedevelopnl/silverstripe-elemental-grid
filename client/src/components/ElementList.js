import { inject } from 'lib/Injector';
import i18n from 'i18n';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import { compose } from 'redux';
import classNames from 'classnames';
import { elementTypeType } from 'types/elementTypeType';
import { elementType } from 'types/elementType';
import { getDragIndicatorIndex } from 'lib/dragHelpers';
import { getElementTypeConfig } from 'state/editor/elementConfig';
import { calculateGridInsertion, validateGridDrop, analyzeGridLayout } from 'lib/gridPositionHelpers';

class ElementList extends Component {
  constructor(props) {
    super(props);
    console.log('[GRID DEBUG] =================== CUSTOM ELEMENTLIST CONSTRUCTOR CALLED ===================');
    console.log('[GRID DEBUG] ElementList props:', Object.keys(props));
  }

  getDragIndicatorIndex() {
    const { dragTargetElementId, draggedItem, blocks, dragSpot } = this.props;
    return getDragIndicatorIndex(
      blocks.map(element => element.id),
      dragTargetElementId,
      draggedItem && draggedItem.id,
      dragSpot
    );
  }

  /**
   * Renders grid-aware drop zones for an element
   */
  renderGridDropZones(element, elementIndex, isFirstElement, isLastElement) {
    const {
      GridDropZoneComponent,
      RowDropZoneComponent,
      allowedElementTypes,
      areaId,
      isDraggingOver,
      blocks
    } = this.props;

    console.log('[GRID DEBUG] renderGridDropZones called for element', element.id, {
      elementIndex,
      isFirstElement,
      isLastElement,
      hasGridDropZone: !!GridDropZoneComponent,
      hasRowDropZone: !!RowDropZoneComponent,
      isDraggingOver,
      allowedElementTypes: (allowedElementTypes && allowedElementTypes.length) || 0
    });

    if (isDraggingOver || !GridDropZoneComponent || !RowDropZoneComponent) {
      console.log('[GRID DEBUG] Skipping grid drop zones:', {
        isDraggingOver,
        missingGridDropZone: !GridDropZoneComponent,
        missingRowDropZone: !RowDropZoneComponent
      });
      return null;
    }

    const gridSchema = element.blockSchema && element.blockSchema.grid;
    const isRow = gridSchema && gridSchema.isRow;
    const gridAnalysis = analyzeGridLayout(blocks);
    const elementInfo = gridAnalysis.elementPositions.get(element.id);

    const dropZones = [];

    // Row-level drop zones (above/below)
    if (isFirstElement) {
      dropZones.push(
        <RowDropZoneComponent
          key={`row-above-${element.id}`}
          areaId={areaId}
          insertAfterElement={elementIndex === 0 ? 0 : blocks[elementIndex - 1].id}
          position="above"
          elementTypes={allowedElementTypes}
          isFirstRow
        />
      );
    }

    if (isRow || isLastElement) {
      dropZones.push(
        <RowDropZoneComponent
          key={`row-below-${element.id}`}
          areaId={areaId}
          insertAfterElement={element.id}
          position="below"
          elementTypes={allowedElementTypes}
          isLastRow={isLastElement}
        />
      );
    }

    // Grid-level drop zones (left/right) - only for non-row elements
    if (!isRow && elementInfo) {
      const rowElements = gridAnalysis.rows[elementInfo.rowIndex] || [];
      const positionInRow = rowElements.findIndex(el => el.id === element.id);
      const isFirstInRow = positionInRow === 0;
      const isLastInRow = positionInRow === rowElements.length - 1;

      // Left drop zone
      if (isFirstInRow) {
        dropZones.push(
          <GridDropZoneComponent
            key={`grid-left-${element.id}`}
            areaId={areaId}
            insertAfterElement={element.id}
            position="left"
            elementTypes={allowedElementTypes}
          />
        );
      }

      // Right drop zone
      if (isLastInRow) {
        dropZones.push(
          <GridDropZoneComponent
            key={`grid-right-${element.id}`}
            areaId={areaId}
            insertAfterElement={element.id}
            position="right"
            elementTypes={allowedElementTypes}
          />
        );
      }
    }

    return dropZones;
  }

  /**
   * Renders a list of Element components with grid-aware drop zones
   */
  renderBlocks() {
    const {
      ElementComponent,
      HoverBarComponent,
      DragIndicatorComponent,
      blocks,
      allowedElementTypes,
      elementTypes,
      areaId,
      onDragEnd,
      onDragOver,
      onDragStart,
      isDraggingOver,
    } = this.props;

    // Blocks can be either null or an empty array
    if (!blocks) {
      return null;
    }

    if (blocks && !blocks.length) {
      return <div>{i18n._t('ElementList.ADD_BLOCKS', 'Add blocks to place your content')}</div>;
    }

    let output = blocks.map((element, index) => {
      const isFirstElement = index === 0;
      const isLastElement = index === blocks.length - 1;
      const gridDropZones = this.renderGridDropZones(element, index, isFirstElement, isLastElement);

      return (
        <div key={element.id} className="element-editor__element-holder">
          {/* Grid drop zones before element */}
          {gridDropZones}

          <ElementComponent
            element={element}
            areaId={areaId}
            type={getElementTypeConfig(element.blockSchema.typeName, elementTypes)}
            link={element.blockSchema.actions.edit}
            onDragOver={onDragOver}
            onDragEnd={onDragEnd}
            onDragStart={onDragStart}
            isDraggedOver={this.props.dragTargetElementId === element.id &&
                this.props.draggedItem && this.props.draggedItem.id !== element.id}
            isDraggedOverPosition={this.props.dragSpot}
          />

          {/* Fallback to standard hover bars if grid components not available */}
          {!this.props.GridDropZoneComponent && !isDraggingOver && (
            <HoverBarComponent
              key={`create-after-${element.id}`}
              areaId={areaId}
              elementId={element.id}
              elementTypes={allowedElementTypes}
            />
          )}
        </div>
      );
    });

    // Add a insert point above the first block for consistency (fallback only)
    if (!isDraggingOver && !this.props.GridDropZoneComponent) {
      output = [
        <HoverBarComponent
          key={0}
          areaId={areaId}
          elementId={0}
          elementTypes={allowedElementTypes}
        />
      ].concat(output);
    }

    // Add drag indicator during drag operations
    const dragIndicatorIndex = this.getDragIndicatorIndex();
    if (isDraggingOver && dragIndicatorIndex !== null && DragIndicatorComponent) {
      output.splice(dragIndicatorIndex, 0, <DragIndicatorComponent key="DropIndicator" />);
    }

    return output;
  }

  /**
   * Renders a loading component
   *
   * @returns {LoadingComponent|null}
   */
  renderLoading() {
    const { loading, LoadingComponent } = this.props;

    if (loading) {
      return <LoadingComponent />;
    }
    return null;
  }

  render() {
    const { blocks, GridDropZoneComponent } = this.props;
    console.log('[GRID DEBUG] ElementList render called with props:', {
      blocksCount: (blocks && blocks.length) || 0,
      hasGridDropZone: !!GridDropZoneComponent,
      propsKeys: Object.keys(this.props)
    });

    const listClassNames = classNames(
      'elemental-editor-list',
      'row',
      {
        'elemental-editor-list--empty': !blocks || !blocks.length,
        'has-grid-drop-zones': !!GridDropZoneComponent
      }
    );

    return this.props.connectDropTarget(
      <div className={listClassNames}>
        {this.renderLoading()}
        {this.renderBlocks()}
      </div>
    );
  }
}

ElementList.propTypes = {
  // @todo support either ElementList or Element children in an array (or both)
  blocks: PropTypes.arrayOf(elementType),
  elementTypes: PropTypes.arrayOf(elementTypeType).isRequired,
  allowedElementTypes: PropTypes.arrayOf(elementTypeType).isRequired,
  loading: PropTypes.bool,
  areaId: PropTypes.number.isRequired,
  dragTargetElementId: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]),
  onDragOver: PropTypes.func,
  onDragStart: PropTypes.func,
  onDragEnd: PropTypes.func,
};

ElementList.defaultProps = {
  blocks: [],
  loading: false,
};

export { ElementList as Component };

const elementListTarget = {
  drop(props, monitor) {
    const { blocks } = props;
    const elementTargetDropResult = monitor.getDropResult();
    const draggedItem = monitor.getItem();

    if (!elementTargetDropResult) {
      return {};
    }

    // Check if this is a grid drop (has gridPosition or rowPosition)
    const isGridDrop = elementTargetDropResult.gridPosition ||
                      elementTargetDropResult.rowPosition ||
                      elementTargetDropResult.isRowDrop;

    if (isGridDrop) {
      // Use grid position translation
      const validation = validateGridDrop(elementTargetDropResult, blocks, draggedItem);
      if (!validation.isValid) {
        console.warn('Invalid grid drop:', validation.reason);
        return {};
      }

      const gridInsertion = calculateGridInsertion(elementTargetDropResult, blocks, draggedItem);

      return {
        ...elementTargetDropResult,
        dropAfterID: gridInsertion.dropAfterID,
        gridMetadata: gridInsertion.gridMetadata,
        insertPosition: gridInsertion.insertPosition,
      };
    } else {
      // Fallback to original logic for standard drops
      const dropIndex = getDragIndicatorIndex(
        blocks.map(element => element.id),
        elementTargetDropResult.target,
        draggedItem,
        elementTargetDropResult.dropSpot,
      );
      const dropAfterID = blocks[dropIndex - 1] ? blocks[dropIndex - 1].id : '0';

      return {
        ...elementTargetDropResult,
        dropAfterID,
      };
    }
  },
};

export default compose(
  DropTarget('element', elementListTarget, (connector, monitor) => ({
    connectDropTarget: connector.dropTarget(),
    draggedItem: monitor.getItem(),
  })),
  inject(
    ['Element', 'Loading', 'HoverBar', 'DragPositionIndicator', 'GridDropZone', 'RowDropZone'],
    (ElementComponent, LoadingComponent, HoverBarComponent, DragIndicatorComponent, GridDropZoneComponent, RowDropZoneComponent) => {
      console.log('[GRID DEBUG] ElementList inject called with components:', {
        ElementComponent: !!ElementComponent,
        LoadingComponent: !!LoadingComponent,
        HoverBarComponent: !!HoverBarComponent,
        DragIndicatorComponent: !!DragIndicatorComponent,
        GridDropZoneComponent: !!GridDropZoneComponent,
        RowDropZoneComponent: !!RowDropZoneComponent,
      });

      return {
        ElementComponent,
        LoadingComponent,
        HoverBarComponent,
        DragIndicatorComponent,
        GridDropZoneComponent,
        RowDropZoneComponent,
      };
    },
    () => 'ElementEditor.ElementList'
  )
)(ElementList);
