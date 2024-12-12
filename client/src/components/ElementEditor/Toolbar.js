import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { inject } from 'lib/Injector';
import { DropTarget } from 'react-dnd';
import { elementTypeType } from 'types/elementTypeType';

// eslint-disable-next-line react/prefer-stateless-function
class Toolbar extends PureComponent {
  render() {
    const {
      AddBlockToBottomButton,
      AddBlockToTopButton,
      elementTypes,
      areaId,
      connectDropTarget,
      onExportJSON
    } = this.props;
    return connectDropTarget(
      <div className="element-editor__toolbar">
        <AddBlockToTopButton
          elementTypes={elementTypes}
          areaId={areaId}
        />
        <AddBlockToBottomButton
          elementTypes={elementTypes}
          areaId={areaId}
        />
        <button onClick={onExportJSON}>Export JSON</button>
      </div>
    );
  }
}

Toolbar.defaultProps = {};
Toolbar.propTypes = {
  elementTypes: PropTypes.arrayOf(elementTypeType).isRequired,
  areaId: PropTypes.number.isRequired,
  AddBlockToBottomButton: PropTypes.oneOfType([PropTypes.node, PropTypes.func]).isRequired,
  AddBlockToTopButton: PropTypes.oneOfType([PropTypes.node, PropTypes.func]).isRequired,
  connectDropTarget: PropTypes.func.isRequired,
  onDragOver: PropTypes.func, // eslint-disable-line react/no-unused-prop-types
  onDragDrop: PropTypes.func, // eslint-disable-line react/no-unused-prop-types
  onExportJSON: PropTypes.func.isRequired,
};

const toolbarTarget = {
  hover(props) {
    const { onDragOver } = props;
    if (onDragOver) {
      onDragOver();
    }
  }
};

export default DropTarget('element', toolbarTarget, connect => ({
  connectDropTarget: connect.dropTarget(),
}))(inject(
  ['AddBlockToBottomButton', 'AddBlockToTopButton'],
  (AddBlockToBottomButton, AddBlockToTopButton) => ({
    AddBlockToBottomButton,
    AddBlockToTopButton
  }),
  () => 'ElementEditor.ElementToolbar'
)(Toolbar));
