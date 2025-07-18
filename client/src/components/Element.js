/* global window */

import { inject } from 'lib/Injector';
import i18n from 'i18n';
import * as TabsActions from 'state/tabs/TabsActions';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { getEmptyImage } from 'react-dnd-html5-backend';
import { DragSource, DropTarget } from 'react-dnd';
import { compose } from 'redux';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { loadElementFormStateName } from 'state/editor/loadElementFormStateName';
import { loadElementSchemaValue } from 'state/editor/loadElementSchemaValue';
import { elementTypeType } from 'types/elementTypeType';
import { elementType } from 'types/elementType';
import { elementDragSource, isOverTop } from 'lib/dragHelpers';

/**
 * The Element component used in the context of an ElementEditor shows the summary
 * of an element's details when used in the CMS, including ID, Title and Summary.
 */
class Element extends Component {
  static getDerivedStateFromError() {
    return { childRenderingError: true };
  }

  constructor(props) {
    super(props);

    console.log('🔧 Grid Element: Constructor called with props:', props);
    console.log('🔧 Grid Element: Element data:', props.element);
    console.log('🔧 Grid Element: Grid schema:', props.element && props.element.blockSchema && props.element.blockSchema.grid);
    console.log('🔧 Grid Element: Full blockSchema:', props.element && props.element.blockSchema);

    // Alert to make sure this is being called
    console.log('🚨 GRID ELEMENT CONSTRUCTOR EXECUTED! 🚨');

    this.handleKeyUp = this.handleKeyUp.bind(this);
    this.handleExpand = this.handleExpand.bind(this);
    this.handleLoadingError = this.handleLoadingError.bind(this);
    this.handleTabClick = this.handleTabClick.bind(this);
    this.updateFormTab = this.updateFormTab.bind(this);
    this.handleChangeSize = this.handleChangeSize.bind(this);
    this.handleChangeOffset = this.handleChangeOffset.bind(this);

    // Safely access grid schema with fallbacks
    const gridSchema = props.element && props.element.blockSchema && props.element.blockSchema.grid;
    const columnData = (gridSchema && gridSchema.column) || {};

    this.state = {
      previewExpanded: false,
      initialTab: '',
      loadingError: false,
      childRenderingError: false,
      size: columnData.size || 12,
      offset: columnData.offset || 0,
    };
  }

  componentDidMount() {
    const { connectDragPreview } = this.props;
    if (connectDragPreview) {
      // Use empty image as a drag preview so browsers don't draw it
      // and we can draw whatever we want on the custom drag layer instead.
      connectDragPreview(getEmptyImage(), {
        // IE fallback: specify that we'd rather screenshot the node
        // when it already knows it's being dragged so we can hide it with CSS.
        captureDraggingState: true,
      });
    }
  }

  /**
   * Returns the applicable versioned state class names for the element
   *
   * @returns {string}
   */
  getVersionedStateClassName() {
    const { element } = this.props;

    const baseClassName = 'element-editor__element';

    if (!element.isPublished) {
      return `${baseClassName}--draft`;
    }

    if (element.isPublished && !element.isLiveVersion) {
      return `${baseClassName}--modified`;
    }

    return `${baseClassName}--published`;
  }

  /**
   * Returns the link title for this element
   *
   * @param {Object} type
   * @returns {string}
   */
  getLinkTitle(type) {
    if (type.broken) {
      return i18n._t('ElementalElement.ARCHIVE_BROKEN', 'Archive this block');
    }
    return i18n.inject(
      i18n._t('ElementalElement.TITLE', 'Edit this {type} block'),
      { type: type.title }
    );
  }

  /**
   * Returns the summary for this elemen
   *
   * @param {Object} element
   * @param {Object} type
   * @returns {string|JSX.Element}
   */
  getSummary(element, type) {
    if (type.broken) {
      // Return a message about the broken block.
      return element.title ? i18n.inject(
        i18n._t(
          'ElementalElement.BROKEN_DESCRIPTION_TITLE',
          'This block had the title "{title}". It is broken and will not display on the front-end. You can archive it to remove it from this elemental area.'
        ),
        { title: element.title }
      ) : i18n._t(
        'ElementalElement.BROKEN_DESCRIPTION',
        'This block is broken and will not display on the front-end. You can archive it to remove it from this elemental area.'
      );
    }
    // Return the configured summary for this block.
    return element.blockSchema.content;
  }

  getColumnSizeClassNames() {
    const { element } = this.props;
    const gridSchema = element && element.blockSchema && element.blockSchema.grid;

    return {
      [`col-lg-${this.state.size}`]: true,
      [`offset-lg-${this.state.offset}`]: this.state.offset > 0,
      'is-row': gridSchema && gridSchema.isRow === true,
      'is-dragged-top': this.props.isDraggedOver && this.props.isDraggedOverPosition === 'top',
      'is-dragged-bottom': this.props.isDraggedOver && this.props.isDraggedOverPosition === 'bottom'
    };
  }

  /**
   * Prevents the Element from being expanded in case a loading error occurred.
   * This gets triggered from the InlineEditForm component.
   */
  handleLoadingError() {
    this.setState({
      loadingError: true
    });
  }

  /**
   * Dispatcher to Tabs redux store for this element's tabset
   *
   * @param {string} activeTab Name prop of the active tab
   */
  updateFormTab(activeTab) {
    const { tabSetName, onActivateTab } = this.props;
    const { initialTab } = this.state;

    if (!initialTab) {
      this.setState({
        initialTab: activeTab
      });
    }

    if (activeTab || initialTab) {
      onActivateTab(tabSetName, activeTab || initialTab);
    } else {
      const defaultFirstTab = 'Main';
      onActivateTab(tabSetName, defaultFirstTab);
    }
  }

  /**
   * Update the active tab on tab actions menu button click event. Is passed down to InlineEditForm.
   *
   * @param {string} toBeActiveTab
   */
  handleTabClick(toBeActiveTab) {
    const { activeTab } = this.props;
    const { loadingError } = this.state;

    if (toBeActiveTab !== activeTab && !loadingError) {
      this.setState({
        previewExpanded: true,
      });

      this.updateFormTab(toBeActiveTab);
    }
  }

  /**
   * Expand the element to show the  preview
   * If the element is not inline-editable, take user to the GridFieldDetailForm to edit the record
   */
  handleExpand(event) {
    const { type, link } = this.props;
    const { loadingError } = this.state;

    if (type.broken) {
      return;
    }

    if (event.target.type === 'button') {
      // Stop bubbling if the click target was a button within this container
      event.stopPropagation();
      return;
    }

    if (type.inlineEditable && !loadingError) {
      this.setState((prevState) => ({
        previewExpanded: !prevState.previewExpanded
      }));
      return;
    }

    // If inline editing is disabled for this element, send them to the standalone
    // edit form
    window.location = link;
  }

  /**
   * If pressing enter or space key, treat it like a mouse click
   *
   * @param {Object} event
   */
  handleKeyUp(event) {
    const { nodeName } = event.target;

    if (
      (event.key === ' ' || event.key === 'Enter')
      // Ignore presses while focusing inputs and textareas
      && !['input', 'textarea'].includes(nodeName.toLowerCase())
    ) {
      this.handleExpand(event);
    }
  }

  handleChangeSize(e) {
    this.setState({
      size: e.target.value
    });
  }

  handleChangeOffset(e) {
    this.setState({
      offset: e.target.value
    });
  }

  render() {
    console.log('🚨 GRID ELEMENT RENDER CALLED! 🚨');

    const {
      element,
      type,
      areaId,
      HeaderComponent,
      ContentComponent,
      ColumnSizeComponent,
      link,
      activeTab,
      connectDragSource,
      connectDropTarget,
      isDragging,
      isOver,
      onDragEnd,
    } = this.props;

    const { childRenderingError, previewExpanded } = this.state;

    if (!element.id) {
      return null;
    }

    const elementClassNames = classNames(
      'element-editor__element',
      {
        'element-editor__element--broken': type.broken,
        'element-editor__element--expandable': type.inlineEditable && !type.broken,
        'element-editor__element--dragging': isDragging,
        'element-editor__element--dragged-over': isOver,
      },
      this.getVersionedStateClassName(),
    );

    const content = connectDropTarget(<div key={element.id} className={classNames('element-editor__element-holder', this.getColumnSizeClassNames())}>
      <div
        className={elementClassNames}
        onClick={this.handleExpand}
        onKeyUp={this.handleKeyUp}
        role="button"
        tabIndex={0}
        title={this.getLinkTitle(type)}
      >
        <HeaderComponent
          element={element}
          type={type}
          areaId={areaId}
          expandable={type.inlineEditable}
          link={link}
          previewExpanded={previewExpanded && !childRenderingError}
          handleEditTabsClick={this.handleTabClick}
          activeTab={activeTab}
          disableTooltip={isDragging}
          onDragEnd={onDragEnd}
        />

        {
          !childRenderingError &&
          <ContentComponent
            id={element.id}
            fileUrl={element.blockSchema.fileURL}
            fileTitle={element.blockSchema.fileTitle}
            content={this.getSummary(element, type)}
            previewExpanded={previewExpanded && !isDragging}
            activeTab={activeTab}
            onFormInit={() => this.updateFormTab(activeTab)}
            handleLoadingError={this.handleLoadingError}
            broken={type.broken}
          />
        }

        {
          childRenderingError &&
          <div className="alert alert-danger mt-2">
            {i18n._t('ElementalElement.CHILD_RENDERING_ERROR', 'Something went wrong with this block. Please try saving and refreshing the CMS.')}
          </div>
        }

        {(() => {
          const gridSchema = element && element.blockSchema && element.blockSchema.grid;
          const columnData = (gridSchema && gridSchema.column) || {};

          console.log('🔧 Grid Element: Checking if should render ColumnSize component');
          console.log('🔧 Grid Element: gridSchema:', gridSchema);
          console.log('🔧 Grid Element: ColumnSizeComponent:', ColumnSizeComponent);
          console.log('🔧 Grid Element: Grid data:', {
            elementId: element.id,
            size: columnData.size,
            defaultViewport: columnData.defaultViewport,
            gridColumns: gridSchema && gridSchema.gridColumns,
            offset: columnData.offset
          });

          if (gridSchema && !gridSchema.isRow && ColumnSizeComponent) {
            console.log('✅ Grid Element: Rendering ColumnSize component');
            return (
              <ColumnSizeComponent
                elementId={element.id}
                size={columnData.size || 12}
                defaultViewport={columnData.defaultViewport || 'LG'}
                gridColumns={gridSchema.gridColumns || 12}
                offset={columnData.offset || 0}
                handleChangeSize={this.handleChangeSize}
                handleChangeOffset={this.handleChangeOffset}
              />
            );
          } else {
            console.log('❌ Grid Element: NOT rendering ColumnSize component', {
              hasGridSchema: !!gridSchema,
              isRow: gridSchema && gridSchema.isRow,
              hasColumnSizeComponent: !!ColumnSizeComponent
            });
            return null;
          }
        })()}
      </div>
    </div>);

    if (!previewExpanded) {
      return connectDragSource(content);
    }

    return content;
  }
}

function mapStateToProps(state, ownProps) {
  const elementId = ownProps.element.id;
  const elementName = loadElementFormStateName(elementId);
  const elementFormSchema = loadElementSchemaValue('schemaUrl', elementId);

  const filterFieldsForTabs = (field) => field.component === 'Tabs';

  // Find name of the first Tabs component in the form
  // Only defined - and needed - once the form is loaded
  const tabSet =
    state.form &&
    state.form.formSchemas[elementFormSchema] &&
    state.form.formSchemas[elementFormSchema].schema &&
    state.form.formSchemas[elementFormSchema].schema.fields.find(filterFieldsForTabs);

  const tabSetName = tabSet && tabSet.id;
  const uniqueFieldId = `element.${elementName}__${tabSetName}`;

  // Find name of the active tab in the tab set
  // Only defined once an element form is expanded for the first time
  const activeTab =
    state.tabs &&
    state.tabs.fields &&
    state.tabs.fields[uniqueFieldId] &&
    state.tabs.fields[uniqueFieldId].activeTab;
  return {
    tabSetName,
    activeTab,
  };
}

function mapDispatchToProps(dispatch, ownProps) {
  const elementName = loadElementFormStateName(ownProps.element.id);

  return {
    onActivateTab(tabSetName, activeTabName) {
      dispatch(TabsActions.activateTab(`element.${elementName}__${tabSetName}`, activeTabName));
    },
  };
}

Element.propTypes = {
  element: elementType,
  type: elementTypeType.isRequired,
  areaId: PropTypes.number.isRequired,
  link: PropTypes.string.isRequired,
  // Redux mapped props:
  activeTab: PropTypes.string,
  tabSetName: PropTypes.string,
  onActivateTab: PropTypes.func,
  connectDragSource: PropTypes.func.isRequired,
  connectDragPreview: PropTypes.func.isRequired,
  connectDropTarget: PropTypes.func.isRequired,
  isDragging: PropTypes.bool.isRequired,
  isOver: PropTypes.bool.isRequired,
  onDragOver: PropTypes.func, // eslint-disable-line react/no-unused-prop-types
  onDragEnd: PropTypes.func, // eslint-disable-line react/no-unused-prop-types
  onDragStart: PropTypes.func, // eslint-disable-line react/no-unused-prop-types
  isDraggedOver: PropTypes.bool,
  isDraggedOverPosition: PropTypes.string
};

Element.defaultProps = {
  element: null,
};

export { Element as Component };

const elementTarget = {
  drop(props, monitor, component) {
    const { element } = props;
    return {
      target: element.id,
      dropSpot: isOverTop(monitor, component, element) ? 'top' : 'bottom',
    };
  },

  hover(props, monitor, component) {
    const { element, onDragOver } = props;

    if (onDragOver) {
      onDragOver(element, isOverTop(monitor, component, element));
    }
  },
};

export default compose(
  DropTarget('element', elementTarget, (connector, monitor) => ({
    connectDropTarget: connector.dropTarget(),
    isOver: monitor.isOver(),
  })),
  DragSource('element', elementDragSource, (connector, monitor) => ({
    connectDragSource: connector.dragSource(),
    connectDragPreview: connector.dragPreview(),
    isDragging: monitor.isDragging(),
  })),
  connect(mapStateToProps, mapDispatchToProps),
  inject(
    ['ElementHeader', 'ElementContent', 'ColumnSize'],
    (HeaderComponent, ContentComponent, ColumnSizeComponent) => ({
      HeaderComponent, ContentComponent, ColumnSizeComponent
    }),
    () => 'Element' // Back to Element since we're directly registering as Element
  )
)(Element);
