import React, {PureComponent} from 'react';
import PropTypes from 'prop-types';
import {compose, bindActionCreators} from 'redux';
import {connect} from 'react-redux';
import {autofill} from 'redux-form';

class ColumnSize extends PureComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
    this.handleChangeSize = this.handleChangeSize.bind(this);
    this.handleChangeOffset = this.handleChangeOffset.bind(this);
  }

  getColSizeSource() {
    let colSizes = [];

    for (let size = 1; size <= this.props.gridColumns; size++) {
      colSizes.push({
        label: `Column ${size}/${this.props.gridColumns}`,
        value: size
      })
    }

    return colSizes
  }

  getOffsetSizeSource() {
    let offsetSizes = [];

    offsetSizes.push({
      label: `None`,
      value: 0
    })

    for (let size = 1; size <= this.props.gridColumns; size++) {
      offsetSizes.push({
        label: `Column ${size}/${this.props.gridColumns}`,
        value: size
      })
    }

    return offsetSizes
  }

  handleClick(event) {
    event.stopPropagation();
  }

  handleChangeSize(event) {
    const {elementId, defaultViewport} = this.props;
    this.props.actions.reduxForm.autofill(
      `element.ElementForm_${elementId}`,
      `PageElements_${elementId}_Size${defaultViewport}`,
      event.target.value
    );
    this.props.handleChangeSize(event);
  }

  handleChangeOffset(event) {
    const {elementId, defaultViewport} = this.props;
    this.props.actions.reduxForm.autofill(
      `element.ElementForm_${elementId}`,
      `PageElements_${elementId}_Offset${defaultViewport}`,
      event.target.value
    );
    this.props.handleChangeOffset(event);
  }

  render() {
    let colSizes = [];
    let offsetSizes = [];

    offsetSizes.push({
      label: `None`,
      value: 0
    })

    for (let size = 1; size <= this.props.gridColumns; size++) {
      offsetSizes.push({
        label: `Column ${size}/${this.props.gridColumns}`,
        value: size
      })
    }

    return (
      <div>
        <hr/>

        <label class="mb-0 font-italic">
          Size { this.props.defaultViewport }
          <select
            defaultValue={this.props.size}
            onChange={this.handleChangeSize}
            onClick={this.handleClick}
          >
            {
              this.getColSizeSource().map((columnObject, index) => (
                <option key={index} value={columnObject.value}>{columnObject.label}</option>
              ))
            }
          </select>
        </label>
        <label class="mb-0 ml-2 font-italic">
          Offset { this.props.defaultViewport }
          <select
            defaultValue={this.props.offset}
            onChange={this.handleChangeOffset}
            onClick={this.handleClick}
          >
            {
              this.getOffsetSizeSource().map((columnObject, index) => (
                <option key={index} value={columnObject.value}>{columnObject.label}</option>
              ))
            }
          </select>
        </label>
      </div>
    );
  }
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      reduxForm: bindActionCreators({autofill}, dispatch),
    },
  };
}

ColumnSize.defaultProps = {};

ColumnSize.propTypes = {
  actions: PropTypes.shape({
    reduxFrom: PropTypes.object,
  }),
  elementId: PropTypes.number,
  size: PropTypes.number,
  defaultViewport: PropTypes.string,
  offset: PropTypes.number,
  gridColumns: PropTypes.number,
  handleChangeSize: PropTypes.func,
  handleChangeOffset: PropTypes.func,
};

export default compose(
  connect(() => {
  }, mapDispatchToProps)
)(ColumnSize);
