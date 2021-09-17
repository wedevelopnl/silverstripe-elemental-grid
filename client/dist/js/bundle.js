/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./client/src/bundles/bundle.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./client/src/bundles/bundle.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _Injector = __webpack_require__(1);

var _Injector2 = _interopRequireDefault(_Injector);

var _ElementList = __webpack_require__("./client/src/components/ElementList.js");

var _ElementList2 = _interopRequireDefault(_ElementList);

var _Element = __webpack_require__("./client/src/components/Element.js");

var _Element2 = _interopRequireDefault(_Element);

var _ColumnSize = __webpack_require__("./client/src/components/ColumnSize.js");

var _ColumnSize2 = _interopRequireDefault(_ColumnSize);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

window.document.addEventListener('DOMContentLoaded', function () {
  _Injector2.default.component.registerMany({
    ElementList: _ElementList2.default,
    Element: _Element2.default,
    ColumnSize: _ColumnSize2.default
  }, { force: true });
});

/***/ }),

/***/ "./client/src/components/ColumnSize.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _react = __webpack_require__(3);

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__(0);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _redux = __webpack_require__(4);

var _reactRedux = __webpack_require__(6);

var _SchemaActions = __webpack_require__(12);

var schemaActions = _interopRequireWildcard(_SchemaActions);

var _reduxForm = __webpack_require__(11);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var ColumnSize = function (_PureComponent) {
  _inherits(ColumnSize, _PureComponent);

  function ColumnSize(props) {
    _classCallCheck(this, ColumnSize);

    var _this = _possibleConstructorReturn(this, (ColumnSize.__proto__ || Object.getPrototypeOf(ColumnSize)).call(this, props));

    _this.handleClick = _this.handleClick.bind(_this);
    _this.handleChange = _this.handleChange.bind(_this);

    _this.state = {
      size: _this.props.size
    };
    return _this;
  }

  _createClass(ColumnSize, [{
    key: 'handleClick',
    value: function handleClick(event) {
      event.stopPropagation();
    }
  }, {
    key: 'handleChange',
    value: function handleChange(event) {
      var elementId = this.props.elementId;

      this.props.actions.reduxForm.autofill('element.ElementForm_' + elementId, 'PageElements_' + elementId + '_SizeMD', event.target.value);
      this.props.handleChange(event);
    }
  }, {
    key: 'render',
    value: function render() {
      var source = [{
        title: 'Column 1/12',
        value: 1
      }, {
        title: 'Column 2/12',
        value: 2
      }, {
        title: 'Column 3/12',
        value: 3
      }, {
        title: 'Column 4/12',
        value: 4
      }, {
        title: 'Column 5/12',
        value: 5
      }, {
        title: 'Column 6/12',
        value: 6
      }, {
        title: 'Column 7/12',
        value: 7
      }, {
        title: 'Column 8/12',
        value: 8
      }, {
        title: 'Column 9/12',
        value: 9
      }, {
        title: 'Column 10/12',
        value: 10
      }, {
        title: 'Column 11/12',
        value: 11
      }, {
        title: 'Column 12/12',
        value: 12
      }];

      return _react2.default.createElement(
        'select',
        { name: 'colMDWidth', defaultValue: this.props.size, onChange: this.handleChange, onClick: this.handleClick },
        source.map(function (_ref) {
          var title = _ref.title,
              id = _ref.id,
              value = _ref.value;
          return _react2.default.createElement(
            'option',
            { key: id, value: value },
            title
          );
        })
      );
    }
  }]);

  return ColumnSize;
}(_react.PureComponent);

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      schema: (0, _redux.bindActionCreators)(schemaActions, dispatch),
      reduxForm: (0, _redux.bindActionCreators)({ autofill: _reduxForm.autofill }, dispatch)
    }
  };
}

ColumnSize.defaultProps = {};

ColumnSize.propTypes = {
  actions: _propTypes2.default.shape({
    schema: _propTypes2.default.object,
    reduxFrom: _propTypes2.default.object
  }),
  elementId: _propTypes2.default.number,
  size: _propTypes2.default.number,
  offset: _propTypes2.default.number,
  handleChange: _propTypes2.default.func
};

exports.default = (0, _redux.compose)((0, _reactRedux.connect)(function () {}, mapDispatchToProps))(ColumnSize);

/***/ }),

/***/ "./client/src/components/Element.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Component = undefined;

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _react = __webpack_require__(3);

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__(0);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _elementType = __webpack_require__("./client/src/types/elementType.js");

var _elementTypeType = __webpack_require__("./client/src/types/elementTypeType.js");

var _redux = __webpack_require__(4);

var _Injector = __webpack_require__(1);

var _i18n = __webpack_require__(8);

var _i18n2 = _interopRequireDefault(_i18n);

var _classnames = __webpack_require__(7);

var _classnames2 = _interopRequireDefault(_classnames);

var _reactRedux = __webpack_require__(6);

var _loadElementFormStateName = __webpack_require__("./client/src/state/editor/loadElementFormStateName.js");

var _loadElementSchemaValue = __webpack_require__("./client/src/state/editor/loadElementSchemaValue.js");

var _TabsActions = __webpack_require__(13);

var TabsActions = _interopRequireWildcard(_TabsActions);

var _reactDnd = __webpack_require__(5);

var _reactDndHtml5Backend = __webpack_require__(9);

var _dragHelpers = __webpack_require__("./client/src/lib/dragHelpers.js");

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Element = function (_Component) {
  _inherits(Element, _Component);

  _createClass(Element, null, [{
    key: 'getDerivedStateFromError',
    value: function getDerivedStateFromError() {
      return { childRenderingError: true };
    }
  }]);

  function Element(props) {
    _classCallCheck(this, Element);

    var _this = _possibleConstructorReturn(this, (Element.__proto__ || Object.getPrototypeOf(Element)).call(this, props));

    _this.handleKeyUp = _this.handleKeyUp.bind(_this);
    _this.handleExpand = _this.handleExpand.bind(_this);
    _this.handleLoadingError = _this.handleLoadingError.bind(_this);
    _this.handleTabClick = _this.handleTabClick.bind(_this);
    _this.updateFormTab = _this.updateFormTab.bind(_this);
    _this.handleChange = _this.handleChange.bind(_this);

    _this.state = {
      previewExpanded: false,
      initialTab: '',
      loadingError: false,
      childRenderingError: false,
      size: props.element.blockSchema.grid.md.size
    };
    return _this;
  }

  _createClass(Element, [{
    key: 'componentDidMount',
    value: function componentDidMount() {
      var connectDragPreview = this.props.connectDragPreview;

      if (connectDragPreview) {
        connectDragPreview((0, _reactDndHtml5Backend.getEmptyImage)(), {
          captureDraggingState: true
        });
      }
    }
  }, {
    key: 'getVersionedStateClassName',
    value: function getVersionedStateClassName() {
      var element = this.props.element;


      var baseClassName = 'element-editor__element';

      if (!element.isPublished) {
        return baseClassName + '--draft';
      }

      if (element.isPublished && !element.isLiveVersion) {
        return baseClassName + '--modified';
      }

      return baseClassName + '--published';
    }
  }, {
    key: 'getColumnSizeClassNames',
    value: function getColumnSizeClassNames() {
      var _ref;

      var element = this.props.element;


      return _ref = {}, _defineProperty(_ref, 'col-md-' + this.state.size, true), _defineProperty(_ref, 'offset-md-' + element.blockSchema.grid.md.offset, true), _defineProperty(_ref, 'is-row', element.blockSchema.grid.isRow === true), _ref;
    }
  }, {
    key: 'handleLoadingError',
    value: function handleLoadingError() {
      this.setState({
        loadingError: true
      });
    }
  }, {
    key: 'updateFormTab',
    value: function updateFormTab(activeTab) {
      var _props = this.props,
          tabSetName = _props.tabSetName,
          onActivateTab = _props.onActivateTab;
      var initialTab = this.state.initialTab;


      if (!initialTab) {
        this.setState({
          initialTab: activeTab
        });
      }

      if (activeTab || initialTab) {
        onActivateTab(tabSetName, activeTab || initialTab);
      } else {
        var defaultFirstTab = 'Main';
        onActivateTab(tabSetName, defaultFirstTab);
      }
    }
  }, {
    key: 'handleTabClick',
    value: function handleTabClick(toBeActiveTab) {
      var activeTab = this.props.activeTab;
      var loadingError = this.state.loadingError;


      if (toBeActiveTab !== activeTab && !loadingError) {
        this.setState({
          previewExpanded: true
        });

        this.updateFormTab(toBeActiveTab);
      }
    }
  }, {
    key: 'handleExpand',
    value: function handleExpand(event) {
      var _props2 = this.props,
          type = _props2.type,
          link = _props2.link;
      var loadingError = this.state.loadingError;


      if (event.target.type === 'button') {
        event.stopPropagation();
        return;
      }

      if (type.inlineEditable && !loadingError) {
        this.setState({
          previewExpanded: !this.state.previewExpanded
        });
        return;
      }

      window.location = link;
    }
  }, {
    key: 'handleKeyUp',
    value: function handleKeyUp(event) {
      var nodeName = event.target.nodeName;


      if ((event.key === ' ' || event.key === 'Enter') && !['input', 'textarea'].includes(nodeName.toLowerCase())) {
        this.handleExpand(event);
      }
    }
  }, {
    key: 'handleChange',
    value: function handleChange(e) {
      this.setState({
        size: e.target.value
      });
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      var _props3 = this.props,
          element = _props3.element,
          type = _props3.type,
          areaId = _props3.areaId,
          HeaderComponent = _props3.HeaderComponent,
          ContentComponent = _props3.ContentComponent,
          ColumnSizeComponent = _props3.ColumnSizeComponent,
          link = _props3.link,
          activeTab = _props3.activeTab,
          connectDragSource = _props3.connectDragSource,
          connectDropTarget = _props3.connectDropTarget,
          isDragging = _props3.isDragging,
          isOver = _props3.isOver,
          onDragEnd = _props3.onDragEnd;
      var _state = this.state,
          childRenderingError = _state.childRenderingError,
          previewExpanded = _state.previewExpanded;


      if (!element.id) {
        return null;
      }

      var linkTitle = _i18n2.default.inject(_i18n2.default._t('ElementalElement.TITLE', 'Edit this {type} block'), { type: type.title });

      var elementClassNames = (0, _classnames2.default)('element-editor__element', {
        'element-editor__element--expandable': type.inlineEditable,
        'element-editor__element--dragging': isDragging,
        'element-editor__element--dragged-over': isOver
      }, this.getVersionedStateClassName(), this.getColumnSizeClassNames());

      var content = connectDropTarget(_react2.default.createElement(
        'div',
        {
          className: elementClassNames,
          onClick: this.handleExpand,
          onKeyUp: this.handleKeyUp,
          role: 'button',
          tabIndex: 0,
          title: linkTitle,
          key: element.id
        },
        _react2.default.createElement(HeaderComponent, {
          element: element,
          type: type,
          areaId: areaId,
          expandable: type.inlineEditable,
          link: link,
          previewExpanded: previewExpanded && !childRenderingError,
          handleEditTabsClick: this.handleTabClick,
          activeTab: activeTab,
          disableTooltip: isDragging,
          onDragEnd: onDragEnd
        }),
        !childRenderingError && _react2.default.createElement(ContentComponent, {
          id: element.id,
          fileUrl: element.blockSchema.fileURL,
          fileTitle: element.blockSchema.fileTitle,
          content: element.blockSchema.content,
          previewExpanded: previewExpanded && !isDragging,
          activeTab: activeTab,
          onFormInit: function onFormInit() {
            return _this2.updateFormTab(activeTab);
          },
          handleLoadingError: this.handleLoadingError
        }),
        childRenderingError && _react2.default.createElement(
          'div',
          { className: 'alert alert-danger mt-2' },
          _i18n2.default._t('ElementalElement.CHILD_RENDERING_ERROR', 'Something went wrong with this block. Please try saving and refreshing the CMS.')
        ),
        !element.blockSchema.grid.isRow && _react2.default.createElement(ColumnSizeComponent, {
          elementId: element.id,
          size: element.blockSchema.grid.md.size,
          offset: element.blockSchema.grid.md.offset,
          handleChange: this.handleChange
        })
      ));

      if (isDragging) {
        return content;
      }

      if (!previewExpanded) {
        return connectDragSource(content);
      }

      return content;
    }
  }]);

  return Element;
}(_react.Component);

function mapStateToProps(state, ownProps) {
  var elementId = ownProps.element.id;
  var elementName = (0, _loadElementFormStateName.loadElementFormStateName)(elementId);
  var elementFormSchema = (0, _loadElementSchemaValue.loadElementSchemaValue)('schemaUrl', elementId);

  var filterFieldsForTabs = function filterFieldsForTabs(field) {
    return field.component === 'Tabs';
  };

  var tabSet = state.form && state.form.formSchemas[elementFormSchema] && state.form.formSchemas[elementFormSchema].schema && state.form.formSchemas[elementFormSchema].schema.fields.find(filterFieldsForTabs);

  var tabSetName = tabSet && tabSet.id;
  var uniqueFieldId = 'element.' + elementName + '__' + tabSetName;

  var activeTab = state.tabs && state.tabs.fields && state.tabs.fields[uniqueFieldId] && state.tabs.fields[uniqueFieldId].activeTab;

  return {
    tabSetName: tabSetName,
    activeTab: activeTab
  };
}

function mapDispatchToProps(dispatch, ownProps) {
  var elementName = (0, _loadElementFormStateName.loadElementFormStateName)(ownProps.element.id);

  return {
    onActivateTab: function onActivateTab(tabSetName, activeTabName) {
      dispatch(TabsActions.activateTab('element.' + elementName + '__' + tabSetName, activeTabName));
    }
  };
}

Element.propTypes = {
  element: _elementType.elementType,
  type: _elementTypeType.elementTypeType.isRequired,
  areaId: _propTypes2.default.number.isRequired,
  link: _propTypes2.default.string.isRequired,

  activeTab: _propTypes2.default.string,
  tabSetName: _propTypes2.default.string,
  onActivateTab: _propTypes2.default.func,
  connectDragSource: _propTypes2.default.func.isRequired,
  connectDragPreview: _propTypes2.default.func.isRequired,
  connectDropTarget: _propTypes2.default.func.isRequired,
  isDragging: _propTypes2.default.bool.isRequired,
  isOver: _propTypes2.default.bool.isRequired,
  onDragOver: _propTypes2.default.func,
  onDragEnd: _propTypes2.default.func,
  onDragStart: _propTypes2.default.func };

Element.defaultProps = {
  element: null
};

exports.Component = Element;


var elementTarget = {
  drop: function drop(props, monitor, component) {
    var element = props.element;


    return {
      target: element.id,
      dropSpot: (0, _dragHelpers.isOverTop)(monitor, component) ? 'top' : 'bottom'
    };
  },
  hover: function hover(props, monitor, component) {
    var element = props.element,
        onDragOver = props.onDragOver;


    if (onDragOver) {
      onDragOver(element, (0, _dragHelpers.isOverTop)(monitor, component));
    }
  }
};

exports.default = (0, _redux.compose)((0, _reactDnd.DropTarget)('element', elementTarget, function (connector, monitor) {
  return {
    connectDropTarget: connector.dropTarget(),
    isOver: monitor.isOver()
  };
}), (0, _reactDnd.DragSource)('element', _dragHelpers.elementDragSource, function (connector, monitor) {
  return {
    connectDragSource: connector.dragSource(),
    connectDragPreview: connector.dragPreview(),
    isDragging: monitor.isDragging()
  };
}), (0, _reactRedux.connect)(mapStateToProps, mapDispatchToProps), (0, _Injector.inject)(['ElementHeader', 'ElementContent', 'ColumnSize'], function (HeaderComponent, ContentComponent, ColumnSizeComponent) {
  return {
    HeaderComponent: HeaderComponent, ContentComponent: ContentComponent, ColumnSizeComponent: ColumnSizeComponent
  };
}, function () {
  return 'ElementEditor.ElementList.Element';
}))(Element);

/***/ }),

/***/ "./client/src/components/ElementList.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Component = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _react = __webpack_require__(3);

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__(0);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _elementType = __webpack_require__("./client/src/types/elementType.js");

var _elementTypeType = __webpack_require__("./client/src/types/elementTypeType.js");

var _redux = __webpack_require__(4);

var _Injector = __webpack_require__(1);

var _classnames = __webpack_require__(7);

var _classnames2 = _interopRequireDefault(_classnames);

var _i18n = __webpack_require__(8);

var _i18n2 = _interopRequireDefault(_i18n);

var _reactDnd = __webpack_require__(5);

var _dragHelpers = __webpack_require__("./client/src/lib/dragHelpers.js");

var _elementConfig = __webpack_require__("./client/src/state/editor/elementConfig.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var ElementList = function (_Component) {
  _inherits(ElementList, _Component);

  function ElementList() {
    _classCallCheck(this, ElementList);

    return _possibleConstructorReturn(this, (ElementList.__proto__ || Object.getPrototypeOf(ElementList)).apply(this, arguments));
  }

  _createClass(ElementList, [{
    key: 'getDragIndicatorIndex',
    value: function getDragIndicatorIndex() {
      var _props = this.props,
          dragTargetElementId = _props.dragTargetElementId,
          draggedItem = _props.draggedItem,
          blocks = _props.blocks,
          dragSpot = _props.dragSpot;

      return (0, _dragHelpers.getDragIndicatorIndex)(blocks.map(function (element) {
        return element.id;
      }), dragTargetElementId, draggedItem && draggedItem.id, dragSpot);
    }
  }, {
    key: 'renderBlocks',
    value: function renderBlocks() {
      var _props2 = this.props,
          ElementComponent = _props2.ElementComponent,
          HoverBarComponent = _props2.HoverBarComponent,
          DragIndicatorComponent = _props2.DragIndicatorComponent,
          blocks = _props2.blocks,
          allowedElementTypes = _props2.allowedElementTypes,
          elementTypes = _props2.elementTypes,
          areaId = _props2.areaId,
          onDragEnd = _props2.onDragEnd,
          onDragOver = _props2.onDragOver,
          onDragStart = _props2.onDragStart,
          isDraggingOver = _props2.isDraggingOver;

      if (!blocks) {
        return null;
      }

      if (blocks && !blocks.length) {
        return _react2.default.createElement(
          'div',
          null,
          _i18n2.default._t('ElementList.ADD_BLOCKS', 'Add blocks to place your content')
        );
      }

      var output = blocks.map(function (element) {
        return _react2.default.createElement(
          ElementComponent,
          {
            key: element.id,
            element: element,
            areaId: areaId,
            type: (0, _elementConfig.getElementTypeConfig)(element.blockSchema.typeName, elementTypes),
            link: element.blockSchema.actions.edit,
            onDragOver: onDragOver,
            onDragEnd: onDragEnd,
            onDragStart: onDragStart
          },
          isDraggingOver || _react2.default.createElement(HoverBarComponent, {
            key: 'create-after-' + element.id,
            areaId: areaId,
            elementId: element.id,
            elementTypes: allowedElementTypes
          })
        );
      });

      if (!isDraggingOver) {
        output = [_react2.default.createElement(HoverBarComponent, {
          key: 0,
          areaId: areaId,
          elementId: 0,
          elementTypes: allowedElementTypes
        })].concat(output);
      }

      var dragIndicatorIndex = this.getDragIndicatorIndex();
      if (isDraggingOver && dragIndicatorIndex !== null) {
        output.splice(dragIndicatorIndex, 0, _react2.default.createElement(DragIndicatorComponent, { key: 'DropIndicator' }));
      }

      return output;
    }
  }, {
    key: 'renderLoading',
    value: function renderLoading() {
      var _props3 = this.props,
          loading = _props3.loading,
          LoadingComponent = _props3.LoadingComponent;


      if (loading) {
        return _react2.default.createElement(LoadingComponent, null);
      }
      return null;
    }
  }, {
    key: 'render',
    value: function render() {
      var blocks = this.props.blocks;

      var listClassNames = (0, _classnames2.default)('elemental-editor-list', 'row', { 'elemental-editor-list--empty': !blocks || !blocks.length });

      return this.props.connectDropTarget(_react2.default.createElement(
        'div',
        { className: listClassNames },
        this.renderLoading(),
        this.renderBlocks()
      ));
    }
  }]);

  return ElementList;
}(_react.Component);

ElementList.propTypes = {
  blocks: _propTypes2.default.arrayOf(_elementType.elementType),
  elementTypes: _propTypes2.default.arrayOf(_elementTypeType.elementTypeType).isRequired,
  allowedElementTypes: _propTypes2.default.arrayOf(_elementTypeType.elementTypeType).isRequired,
  loading: _propTypes2.default.bool,
  areaId: _propTypes2.default.number.isRequired,
  dragTargetElementId: _propTypes2.default.oneOfType([_propTypes2.default.string, _propTypes2.default.bool]),
  onDragOver: _propTypes2.default.func,
  onDragStart: _propTypes2.default.func,
  onDragEnd: _propTypes2.default.func
};

ElementList.defaultProps = {
  blocks: [],
  loading: false
};

exports.Component = ElementList;


var elementListTarget = {
  drop: function drop(props, monitor) {
    var blocks = props.blocks;

    var elementTargetDropResult = monitor.getDropResult();

    if (!elementTargetDropResult) {
      return {};
    }

    var dropIndex = (0, _dragHelpers.getDragIndicatorIndex)(blocks.map(function (element) {
      return element.id;
    }), elementTargetDropResult.target, monitor.getItem(), elementTargetDropResult.dropSpot);
    var dropAfterID = blocks[dropIndex - 1] ? blocks[dropIndex - 1].id : '0';

    return _extends({}, elementTargetDropResult, {
      dropAfterID: dropAfterID
    });
  }
};

exports.default = (0, _redux.compose)((0, _reactDnd.DropTarget)('element', elementListTarget, function (connector, monitor) {
  return {
    connectDropTarget: connector.dropTarget(),
    draggedItem: monitor.getItem()
  };
}), (0, _Injector.inject)(['Element', 'Loading', 'HoverBar', 'DragPositionIndicator'], function (ElementComponent, LoadingComponent, HoverBarComponent, DragIndicatorComponent) {
  return {
    ElementComponent: ElementComponent,
    LoadingComponent: LoadingComponent,
    HoverBarComponent: HoverBarComponent,
    DragIndicatorComponent: DragIndicatorComponent
  };
}, function () {
  return 'ElementEditor.ElementList';
}))(ElementList);

/***/ }),

/***/ "./client/src/lib/dragHelpers.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.elementDragSource = exports.getDragIndicatorIndex = exports.isOverTop = undefined;

var _reactDom = __webpack_require__(10);

var isOverTop = exports.isOverTop = function isOverTop(monitor, component) {
  var clientOffset = monitor.getClientOffset();
  var componentRect = (0, _reactDom.findDOMNode)(component).getBoundingClientRect();

  return clientOffset.y < componentRect.y + componentRect.height / 2;
};

var getDragIndicatorIndex = exports.getDragIndicatorIndex = function getDragIndicatorIndex(items, dragTarget, draggedItem, dragSpot) {
  if (dragTarget === null || !draggedItem) {
    return null;
  }

  var targetIndex = dragTarget ? items.findIndex(function (element) {
    return element === dragTarget;
  }) : 0;
  var sourceIndex = items.findIndex(function (item) {
    return item === draggedItem;
  });

  if (dragSpot === 'bottom') {
    targetIndex += 1;
  }

  if (sourceIndex === targetIndex || sourceIndex + 1 === targetIndex) {
    return null;
  }

  return targetIndex;
};

var elementDragSource = exports.elementDragSource = {
  beginDrag: function beginDrag(props) {
    return props.element;
  },
  endDrag: function endDrag(props, monitor) {
    var onDragEnd = props.onDragEnd;

    var dropResult = monitor.getDropResult();

    if (!onDragEnd || !dropResult || !dropResult.dropAfterID) {
      return;
    }

    var itemID = monitor.getItem().id;
    var dropAfterID = dropResult.dropAfterID;

    if (itemID !== dropAfterID) {
      onDragEnd(itemID, dropAfterID);
    }
  }
};

/***/ }),

/***/ "./client/src/state/editor/elementConfig.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getElementTypeConfig = exports.getConfig = undefined;

var _Config = __webpack_require__(2);

var _Config2 = _interopRequireDefault(_Config);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var getConfig = exports.getConfig = function getConfig() {
  return _Config2.default.getSection('DNADesign\\Elemental\\Controllers\\ElementalAreaController');
};

var getElementTypeConfig = exports.getElementTypeConfig = function getElementTypeConfig(elementType) {
  var typeConfig = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  var types = Array.isArray(typeConfig) ? typeConfig : getConfig().elementTypes;

  return types.find(function (value) {
    return value.class === elementType || value.name === elementType;
  });
};

/***/ }),

/***/ "./client/src/state/editor/loadElementFormStateName.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.loadElementFormStateName = undefined;

var _Config = __webpack_require__(2);

var _Config2 = _interopRequireDefault(_Config);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var loadElementFormStateName = exports.loadElementFormStateName = function loadElementFormStateName() {
  var elementId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

  var sectionKey = 'DNADesign\\Elemental\\Controllers\\ElementalAreaController';
  var section = _Config2.default.getSection(sectionKey);
  var formNameTemplate = section.form.elementForm.formNameTemplate;

  if (elementId) {
    return formNameTemplate.replace('{id}', elementId);
  }
  return formNameTemplate;
};

/***/ }),

/***/ "./client/src/state/editor/loadElementSchemaValue.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.loadElementSchemaValue = undefined;

var _Config = __webpack_require__(2);

var _Config2 = _interopRequireDefault(_Config);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var loadElementSchemaValue = exports.loadElementSchemaValue = function loadElementSchemaValue(key) {
  var elementId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  var sectionKey = 'DNADesign\\Elemental\\Controllers\\ElementalAreaController';
  var section = _Config2.default.getSection(sectionKey);
  var schemaValue = section.form.elementForm[key] || '';

  if (elementId) {
    return schemaValue + '/' + elementId;
  }
  return schemaValue;
};

/***/ }),

/***/ "./client/src/types/elementType.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.elementType = undefined;

var _propTypes = __webpack_require__(0);

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var elementType = _propTypes2.default.shape({
  id: _propTypes2.default.string.isRequired,
  title: _propTypes2.default.string,
  blockSchema: _propTypes2.default.object,
  inlineEditable: _propTypes2.default.bool,
  published: _propTypes2.default.bool,
  liveVersion: _propTypes2.default.bool,
  version: _propTypes2.default.number
});

exports.elementType = elementType;

/***/ }),

/***/ "./client/src/types/elementTypeType.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.elementTypeType = undefined;

var _propTypes = __webpack_require__(0);

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var elementTypeType = _propTypes2.default.shape({
  name: _propTypes2.default.string,

  title: _propTypes2.default.string,

  icon: _propTypes2.default.string,

  inlineEditable: _propTypes2.default.boolean,

  editTabs: _propTypes2.default.arrayOf(_propTypes2.default.shape({
    title: _propTypes2.default.string,
    name: _propTypes2.default.string
  })),

  config: _propTypes2.default.object
});

exports.elementTypeType = elementTypeType;

/***/ }),

/***/ 0:
/***/ (function(module, exports) {

module.exports = PropTypes;

/***/ }),

/***/ 1:
/***/ (function(module, exports) {

module.exports = Injector;

/***/ }),

/***/ 10:
/***/ (function(module, exports) {

module.exports = ReactDom;

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

module.exports = ReduxForm;

/***/ }),

/***/ 12:
/***/ (function(module, exports) {

module.exports = SchemaActions;

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

module.exports = TabsActions;

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

module.exports = Config;

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

module.exports = React;

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

module.exports = Redux;

/***/ }),

/***/ 5:
/***/ (function(module, exports) {

module.exports = ReactDND;

/***/ }),

/***/ 6:
/***/ (function(module, exports) {

module.exports = ReactRedux;

/***/ }),

/***/ 7:
/***/ (function(module, exports) {

module.exports = classnames;

/***/ }),

/***/ 8:
/***/ (function(module, exports) {

module.exports = i18n;

/***/ }),

/***/ 9:
/***/ (function(module, exports) {

module.exports = ReactDNDHtml5Backend;

/***/ })

/******/ });
//# sourceMappingURL=bundle.js.map