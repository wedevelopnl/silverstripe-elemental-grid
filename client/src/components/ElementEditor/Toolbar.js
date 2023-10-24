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
      connectDropTarget } = this.props;
    return connectDropTarget(
      <div className="element-editor__toolbar">
        <AddBlockToBottomButton
          elementTypes={elementTypes}
          areaId={areaId}
        />
        <AddBlockToTopButton
          elementTypes={elementTypes}
          areaId={areaId}
        />
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
