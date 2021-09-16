import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

class ColumnSize extends PureComponent {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
  }

  handleClick(e) {
    e.stopPropagation();
  }

  render() {
    const colSizes = [
      {
        name: 'Column 1/12',
        value: 1
      },
      {
        name: 'Column 2/12',
        value: 2
      },
      {
        name: 'Column 3/12',
        value: 3
      },
      {
        name: 'Column 4/12',
        value: 4
      },
      {
        name: 'Column 5/12',
        value: 5
      },
      {
        name: 'Column 6/12',
        value: 6
      },
      {
        name: 'Column 7/12',
        value: 7
      },
      {
        name: 'Column 8/12',
        value: 8
      },
      {
        name: 'Column 9/12',
        value: 9
      },
      {
        name: 'Column 10/12',
        value: 10
      },
      {
        name: 'Column 11/12',
        value: 11
      },
      {
        name: 'Column 12/12',
        value: 12
      },
    ]

    return (
      <div className="element-editor-column-size">
        <select name="colMDWidth" defaultValue={ this.props.size } onChange={ this.props.handleChange } onClick={ this.handleClick }>
          {
            colSizes.map(({name, id, value}) => (
              <option key={id} value={value}>{name}</option>
            ))
          }
        </select>
      </div>
    );
  }
}

ColumnSize.defaultProps = {};

ColumnSize.propTypes = {
  size: PropTypes.number,
  offset: PropTypes.number,
  handleChange: PropTypes.func,
};

export default ColumnSize;
