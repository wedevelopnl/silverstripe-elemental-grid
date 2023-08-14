import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Button } from 'reactstrap';
import { inject } from 'lib/Injector';
import { elementTypeType } from 'types/elementTypeType';

class AddBlockToBottomButton extends Component {
  constructor(props) {
    super(props);

    this.toggle = this.toggle.bind(this);

    this.state = {
      bottomPopoverOpen: false
    };
  }

  toggle() {
    this.setState(prevState => ({
      bottomPopoverOpen: !prevState.bottomPopoverOpen
    }));
  }

  /**
   * Render the add button for block types
   * @returns {DOMElement}
   */
  render() {
    const { AddElementPopoverComponent, elementTypes, areaId } = this.props;
    const buttonAttributes = {
      id: `ElementalArea${areaId}_AddToBottomButton`,
      color: 'primary',
      onClick: this.toggle,
      className: 'font-icon-plus',
      style: {
        float: 'left'
      }
    };

    return (
      <div>
        <Button {...buttonAttributes}>
          Add to bottom
        </Button>
        <AddElementPopoverComponent
          placement="bottom-start"
          target={buttonAttributes.id}
          isOpen={this.state.bottomPopoverOpen}
          elementTypes={elementTypes}
          toggle={this.toggle}
          areaId={areaId}
          insertAfterElement={0}
          insertAtBottom
        />
      </div>
    );
  }
}

AddBlockToBottomButton.defaultProps = {};
AddBlockToBottomButton.propTypes = {
  elementTypes: PropTypes.arrayOf(elementTypeType).isRequired,
  areaId: PropTypes.number.isRequired,
};

export { AddBlockToBottomButton as Component };

export default inject(
  ['AddElementPopover'],
  (AddElementPopoverComponent) => ({
    AddElementPopoverComponent,
  }),
  () => 'ElementEditor.ElementList.AddBlockToBottomButton'
)(AddBlockToBottomButton);
