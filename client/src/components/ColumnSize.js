import PropTypes from 'prop-types';
import { Component } from 'react';
import { Input } from 'reactstrap';

class ColumnSize extends Component {
  constructor(props) {
    super(props);
    this.state = {
      currentSize: props.size || 12,
      currentOffset: props.offset || 0,
    };
    this.handleChangeSize = this.handleChangeSize.bind(this);
    this.handleChangeOffset = this.handleChangeOffset.bind(this);
  }

  componentDidUpdate(prevProps) {
    // Update state if props change (e.g., after a successful mutation)
    if (prevProps.size !== this.props.size) {
      this.setState({ currentSize: this.props.size || 12 });
    }
    if (prevProps.offset !== this.props.offset) {
      this.setState({ currentOffset: this.props.offset || 0 });
    }
  }

  getColSizeOptions() {
    const colSizes = [];
    for (let size = 1; size <= this.props.gridColumns; size++) {
      colSizes.push({
        value: size,
        title: `Column ${size}/${this.props.gridColumns}`
      });
    }
    return colSizes;
  }

  getOffsetOptions() {
    const offsets = [];
    offsets.push({
      value: 0,
      title: 'None'
    });
    for (let size = 1; size <= this.props.gridColumns; size++) {
      offsets.push({
        value: size,
        title: `Column ${size}/${this.props.gridColumns}`
      });
    }
    return offsets;
  }

  handleChangeSize(event) {
    const newSize = parseInt(event.target.value, 10);
    this.setState({ currentSize: newSize });

    // Update via GraphQL mutation using the correct viewport
    const viewport = this.props.defaultViewport || 'MD';
    const sizeField = `size${viewport}`;
    this.updateElementGrid({ [sizeField]: newSize });

    if (typeof this.props.onChangeSize === 'function') {
      this.props.onChangeSize(event, {
        id: this.props.id,
        value: newSize,
        elementId: this.props.elementId,
        field: 'size'
      });
    }
  }

  handleChangeOffset(event) {
    const newOffset = parseInt(event.target.value, 10);
    this.setState({ currentOffset: newOffset });

    // Update via GraphQL mutation using the correct viewport
    const viewport = this.props.defaultViewport || 'MD';
    const offsetField = `offset${viewport}`;
    this.updateElementGrid({ [offsetField]: newOffset });

    if (typeof this.props.onChangeOffset === 'function') {
      this.props.onChangeOffset(event, {
        id: this.props.id,
        value: newOffset,
        elementId: this.props.elementId,
        field: 'offset'
      });
    }
  }

  updateElementGrid(gridData) {
    const { elementId } = this.props;

    // Get CSRF token from SilverStripe's window.ss.config
    const csrfTokenValue = window.ss && window.ss.config && window.ss.config.SecurityID
      ? window.ss.config.SecurityID
      : null;

    if (!csrfTokenValue) {
      console.error('[Grid] CSRF token is missing. Aborting GraphQL request for element grid update.');
      return;
    }

    // Make direct GraphQL call to update element grid properties
    const query = `
      mutation UpdateElementGrid(
        $id: ID!,
        $sizeXS: Int,
        $sizeSM: Int,
        $sizeMD: Int,
        $sizeLG: Int,
        $sizeXL: Int,
        $offsetXS: Int,
        $offsetSM: Int,
        $offsetMD: Int,
        $offsetLG: Int,
        $offsetXL: Int
      ) {
        updateElementGrid(
          id: $id,
          sizeXS: $sizeXS,
          sizeSM: $sizeSM,
          sizeMD: $sizeMD,
          sizeLG: $sizeLG,
          sizeXL: $sizeXL,
          offsetXS: $offsetXS,
          offsetSM: $offsetSM,
          offsetMD: $offsetMD,
          offsetLG: $offsetLG,
          offsetXL: $offsetXL
        ) {
          id
          sizeXS
          sizeSM
          sizeMD
          sizeLG
          sizeXL
          offsetXS
          offsetSM
          offsetMD
          offsetLG
          offsetXL
        }
      }
    `;

    const variables = {
      id: elementId,
      ...gridData,
    };

    const headers = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfTokenValue,
    };

    // Use fetch to call the GraphQL endpoint directly
    fetch('/admin/graphql', {
      method: 'POST',
      headers,
      credentials: 'same-origin',
      body: JSON.stringify({
        query,
        variables,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((result) => {
        if (result.errors) {
          console.error('[Grid] GraphQL mutation errors:', result.errors);
          // Log detailed error information
          result.errors.forEach((error) => {
            console.error('[Grid] Error details:', error.message, error);
          });
          // Notify user of failure
          if (window.statusMessage) {
            window.statusMessage('Failed to update grid properties. Please try again.', 'error');
          }
        } else {
          console.log('[Grid] Successfully updated element grid properties:', result.data);
        }
      })
      .catch((error) => {
        console.error('[Grid] Failed to update element grid properties:', error);
        // Notify user of network or other errors
        if (window.statusMessage) {
          window.statusMessage('Failed to update grid properties. Please check your connection and try again.', 'error');
        }
      });
  }

  render() {
    const sizeId = `columnSize-${this.props.elementId}`;
    const offsetId = `columnOffset-${this.props.elementId}`;

    return (
      <div className="column-size-controls">
        <hr />
        <div className="form-row">
          <div className="col-sm-6">
            <label htmlFor={sizeId} className="col-form-label">
              Size {this.props.defaultViewport}
            </label>
            <Input
              type="select"
              id={sizeId}
              value={this.state.currentSize}
              onChange={this.handleChangeSize}
              className="form-control"
            >
              {this.getColSizeOptions().map((option) => (
                <option key={`size-${option.value}`} value={option.value}>
                  {option.title}
                </option>
              ))}
            </Input>
          </div>

          <div className="col-sm-6">
            <label htmlFor={offsetId} className="col-form-label">
              Offset {this.props.defaultViewport}
            </label>
            <Input
              type="select"
              id={offsetId}
              value={this.state.currentOffset}
              onChange={this.handleChangeOffset}
              className="form-control"
            >
              {this.getOffsetOptions().map((option) => (
                <option key={`offset-${option.value}`} value={option.value}>
                  {option.title}
                </option>
              ))}
            </Input>
          </div>
        </div>
      </div>
    );
  }
}

ColumnSize.propTypes = {
  elementId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  size: PropTypes.number,
  offset: PropTypes.number,
  defaultViewport: PropTypes.string,
  gridColumns: PropTypes.number,
  onChangeSize: PropTypes.func,
  onChangeOffset: PropTypes.func,
  id: PropTypes.string,
  updateElementGrid: PropTypes.func,
};

ColumnSize.defaultProps = {
  size: 12,
  offset: 0,
  defaultViewport: 'MD',
  gridColumns: 12,
  onChangeSize: null,
  onChangeOffset: null,
  id: '',
  updateElementGrid: null,
};

export default ColumnSize;
