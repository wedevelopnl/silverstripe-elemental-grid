import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { compose, bindActionCreators } from 'redux';
import { connect } from "react-redux";
import * as schemaActions from 'state/schema/SchemaActions';
import { autofill } from 'redux-form';

class ColumnSize extends PureComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
    this.handleChange = this.handleChange.bind(this);

    this.state = {
      size: this.props.size,
    };
  }

  handleClick(event) {
    event.stopPropagation();
  }

  handleChange(event) {
    const {elementId} = this.props;
    this.props.actions.reduxForm.autofill(
      `element.ElementForm_${elementId}`,
      `PageElements_${elementId}_SizeMD`,
      event.target.value
    );
    this.props.handleChange(event);
  }

  render() {
    const source = [
      {
        title: 'Column 1/12',
        value: 1
      },
      {
        title: 'Column 2/12',
        value: 2
      },
      {
        title: 'Column 3/12',
        value: 3
      },
      {
        title: 'Column 4/12',
        value: 4
      },
      {
        title: 'Column 5/12',
        value: 5
      },
      {
        title: 'Column 6/12',
        value: 6
      },
      {
        title: 'Column 7/12',
        value: 7
      },
      {
        title: 'Column 8/12',
        value: 8
      },
      {
        title: 'Column 9/12',
        value: 9
      },
      {
        title: 'Column 10/12',
        value: 10
      },
      {
        title: 'Column 11/12',
        value: 11
      },
      {
        title: 'Column 12/12',
        value: 12
      },
    ];

    return (
      <select name="colMDWidth" defaultValue={this.props.size} onChange={this.handleChange} onClick={this.handleClick}>
        {
          source.map(({title, id, value}) => (
            <option key={id} value={value}>{title}</option>
          ))
        }
      </select>
    );
  }
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      schema: bindActionCreators(schemaActions, dispatch),
      reduxForm: bindActionCreators({ autofill }, dispatch),
    },
  };
}

ColumnSize.defaultProps = {};

ColumnSize.propTypes = {
  actions: PropTypes.shape({
    schema: PropTypes.object,
    reduxFrom: PropTypes.object,
  }),
  elementId: PropTypes.number,
  size: PropTypes.number,
  offset: PropTypes.number,
  handleChange: PropTypes.func,
};

export default compose(
  connect(() => {}, mapDispatchToProps)
)(ColumnSize);
