import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Button } from 'reactstrap';
import { elementTypeType } from 'types/elementTypeType';
import { inject } from 'lib/Injector';

class AddBlockToTopButton extends Component {
  constructor(props) {
    super(props);

    this.toggle = this.toggle.bind(this);

    this.state = {
      popoverOpen: false
    };
  }

  toggle() {
    this.setState({
      topPopoverOpen: !this.state.topPopoverOpen
    });
  }

  /**
   * Render the add button for block types
   * @returns {DOMElement}
   */
  render() {
    const { AddElementPopoverComponent, elementTypes, areaId } = this.props;
    const buttonAttributes = {
      id: `ElementalArea${areaId}_AddToTopButton`,
      color: 'primary',
      onClick: this.toggle,
      className: 'font-icon-plus',
    };

    return (
      <div>
        <Button {...buttonAttributes}>
          {'Add to top'}
        </Button>
        <AddElementPopoverComponent
          placement="bottom-start"
          target={buttonAttributes.id}
          isOpen={this.state.topPopoverOpen}
          elementTypes={elementTypes}
          toggle={this.toggle}
          areaId={areaId}
          insertAfterElement={0}
          insertAtBottom={false}
        />
      </div>
    );
  }
}

AddBlockToTopButton.defaultProps = {};
AddBlockToTopButton.propTypes = {
  elementTypes: PropTypes.arrayOf(elementTypeType).isRequired,
  areaId: PropTypes.number.isRequired,
};

export { AddBlockToTopButton as Component };

export default inject(
  ['AddElementPopover'],
  (AddElementPopoverComponent) => ({
    AddElementPopoverComponent,
  }),
  () => 'ElementEditor.ElementList.AddBlockToTopButton'
)(AddBlockToTopButton);
