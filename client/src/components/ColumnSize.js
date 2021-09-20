import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { compose, bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { autofill } from 'redux-form';

class ColumnSize extends PureComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
    this.handleChangeSize = this.handleChangeSize.bind(this);
    this.handleChangeOffset = this.handleChangeOffset.bind(this);
  }

  handleClick(event) {
    event.stopPropagation();
  }

  handleChangeSize(event) {
    const {elementId} = this.props;
    this.props.actions.reduxForm.autofill(
      `element.ElementForm_${elementId}`,
      `PageElements_${elementId}_SizeMD`,
      event.target.value
    );
    this.props.handleChangeSize(event);
  }

  handleChangeOffset(event) {
    const {elementId} = this.props;
    this.props.actions.reduxForm.autofill(
      `element.ElementForm_${elementId}`,
      `PageElements_${elementId}_OffsetMD`,
      event.target.value
    );
    this.props.handleChangeOffset(event);
  }

  render() {
    const source = [
      {
        label: 'Column 1/12',
        value: 1
      },
      {
        label: 'Column 2/12',
        value: 2
      },
      {
        label: 'Column 3/12',
        value: 3
      },
      {
        label: 'Column 4/12',
        value: 4
      },
      {
        label: 'Column 5/12',
        value: 5
      },
      {
        label: 'Column 6/12',
        value: 6
      },
      {
        label: 'Column 7/12',
        value: 7
      },
      {
        label: 'Column 8/12',
        value: 8
      },
      {
        label: 'Column 9/12',
        value: 9
      },
      {
        label: 'Column 10/12',
        value: 10
      },
      {
        label: 'Column 11/12',
        value: 11
      },
      {
        label: 'Column 12/12',
        value: 12
      },
    ];

    return (
      <div>
        <hr />

        <label class="mb-0 font-italic">
          Size
          <select
            defaultValue={this.props.size}
            onChange={this.handleChangeSize}
            onClick={this.handleClick}
          >
            {
              [1,2,3,4,5,6,7,8,9,10,11,12].map((value, index) => (
                <option key={index} value={value}>{value}/12</option>
              ))
            }
          </select>
        </label>
        <label class="mb-0 ml-2 font-italic">
          Offset
          <select
            defaultValue={this.props.offset}
            onChange={this.handleChangeOffset}
            onClick={this.handleClick}
          >
            {
              [0,1,2,3,4,5,6,7,8,9,10,11,12].map((value, index) => (
                <option key={index} value={value}>{value}/12</option>
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
  offset: PropTypes.number,
  handleChangeSize: PropTypes.func,
  handleChangeOffset: PropTypes.func,
};

export default compose(
  connect(() => {}, mapDispatchToProps)
)(ColumnSize);
