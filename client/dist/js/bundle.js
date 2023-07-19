!function(e){function t(r){if(n[r])return n[r].exports;var o=n[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,t),o.l=!0,o.exports}var n={};t.m=e,t.c=n,t.i=function(e){return e},t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:r})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s="./client/src/bundles/bundle.js")}({"./client/src/bundles/bundle.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}var o=n(2),a=r(o),l=n("./client/src/components/ElementList.js"),i=r(l),u=n("./client/src/components/Element.js"),s=r(u),c=n("./client/src/components/ColumnSize.js"),d=r(c),f=n("./client/src/components/AddBlockToBottomButton.js"),p=r(f),m=n("./client/src/components/AddBlockToTopButton.js"),g=r(m),h=n("./client/src/components/ElementEditor/Toolbar.js"),b=r(h),v=n("./client/src/state/editor/addElementMutation.js"),y=r(v),E=n("./client/src/components/AddElementPopover.js"),T=r(E),O=n(1),_=r(O),C=function(){return function(e){return _.default.createElement("div",null,_.default.createElement(b.default,e))}},j=function(){return function(e){return _.default.createElement("div",null,_.default.createElement(T.default,e))}};window.document.addEventListener("DOMContentLoaded",function(){a.default.component.registerMany({ElementList:i.default,Element:s.default,ColumnSize:d.default,AddBlockToBottomButton:p.default,AddBlockToTopButton:g.default},{force:!0}),a.default.transform("toolbar-override",function(e){e.component("ElementToolbar",C)}),a.default.transform("cms-element-adder-override",function(e){e.component("AddElementPopover",y.default,"ElementAddButton")}),a.default.transform("popover-override",function(e){e.component("AddElementPopover",j)})})},"./client/src/components/AddBlockToBottomButton.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0}),t.Component=void 0;var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),u=n(1),s=r(u),c=n(0),d=r(c),f=n(11),p=n("./client/src/types/elementTypeType.js"),m=n(2),g=function(e){function t(e){o(this,t);var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.toggle=n.toggle.bind(n),n.state={popoverOpen:!1},n}return l(t,e),i(t,[{key:"toggle",value:function(){this.setState({bottomPopoverOpen:!this.state.bottomPopoverOpen})}},{key:"render",value:function(){var e=this.props,t=e.AddElementPopoverComponent,n=e.elementTypes,r=e.areaId,o={id:"ElementalArea"+r+"_AddToBottomButton",color:"primary",onClick:this.toggle,className:"font-icon-plus",style:{float:"left"}};return s.default.createElement("div",null,s.default.createElement(f.Button,o,"Add to bottom"),s.default.createElement(t,{placement:"bottom-start",target:o.id,isOpen:this.state.bottomPopoverOpen,elementTypes:n,toggle:this.toggle,areaId:r,insertAfterElement:0,insertAtBottom:!0}))}}]),t}(u.Component);g.defaultProps={},g.propTypes={elementTypes:d.default.arrayOf(p.elementTypeType).isRequired,areaId:d.default.number.isRequired},t.Component=g,t.default=(0,m.inject)(["AddElementPopover"],function(e){return{AddElementPopoverComponent:e}},function(){return"ElementEditor.ElementList.AddBlockToBottomButton"})(g)},"./client/src/components/AddBlockToTopButton.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0}),t.Component=void 0;var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),u=n(1),s=r(u),c=n(0),d=r(c),f=n(11),p=n("./client/src/types/elementTypeType.js"),m=n(2),g=function(e){function t(e){o(this,t);var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.toggle=n.toggle.bind(n),n.state={popoverOpen:!1},n}return l(t,e),i(t,[{key:"toggle",value:function(){this.setState({topPopoverOpen:!this.state.topPopoverOpen})}},{key:"render",value:function(){var e=this.props,t=e.AddElementPopoverComponent,n=e.elementTypes,r=e.areaId,o={id:"ElementalArea"+r+"_AddToTopButton",color:"primary",onClick:this.toggle,className:"font-icon-plus"};return s.default.createElement("div",null,s.default.createElement(f.Button,o,"Add to top"),s.default.createElement(t,{placement:"bottom-start",target:o.id,isOpen:this.state.topPopoverOpen,elementTypes:n,toggle:this.toggle,areaId:r,insertAfterElement:0,insertAtBottom:!1}))}}]),t}(u.Component);g.defaultProps={},g.propTypes={elementTypes:d.default.arrayOf(p.elementTypeType).isRequired,areaId:d.default.number.isRequired},t.Component=g,t.default=(0,m.inject)(["AddElementPopover"],function(e){return{AddElementPopoverComponent:e}},function(){return"ElementEditor.ElementList.AddBlockToTopButton"})(g)},"./client/src/components/AddElementPopover.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),u=n(1),s=r(u),c=n(0),d=r(c),f=n(6),p=r(f),m=n(2),g=n("./client/src/types/elementTypeType.js"),h=n(7),b=r(h),v=function(e){function t(e){o(this,t);var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.handleToggle=n.handleToggle.bind(n),n}return l(t,e),i(t,[{key:"getElementButtonClickHandler",value:function(e){var t=this;return function(n){var r=t.props,o=r.actions.handleAddElementToArea,a=r.insertAfterElement,l=r.insertAtBottom;n.preventDefault(),o(e.class,a,l).then(function(){var e=window.jQuery(".cms-preview");e.entwine("ss.preview")._loadUrl(e.find("iframe").attr("src"))}),t.handleToggle()}}},{key:"handleToggle",value:function(){(0,this.props.toggle)()}},{key:"render",value:function(){var e=this,t=this.props,n=t.PopoverOptionSetComponent,r=t.elementTypes,o=t.container,a=t.extraClass,l=t.isOpen,i=t.placement,u=t.target,c=(0,p.default)("element-editor-add-element",a),d=r.map(function(t){return{content:t.title,key:t.name,className:(0,p.default)(t.icon,"btn--icon-xl","element-editor-add-element__button"),onClick:e.getElementButtonClickHandler(t)}});return s.default.createElement(n,{buttons:d,searchPlaceholder:b.default._t("ElementAddElementPopover.SEARCH_BLOCKS","Search blocks"),extraClass:c,container:o,isOpen:l,placement:i,target:u,toggle:this.handleToggle})}}]),t}(u.Component);v.propTypes={container:d.default.oneOfType([d.default.string,d.default.func,d.default.object]),elementTypes:d.default.arrayOf(g.elementTypeType).isRequired,extraClass:d.default.oneOfType([d.default.string,d.default.array,d.default.object]),isOpen:d.default.bool.isRequired,placement:d.default.string,target:d.default.oneOfType([d.default.string,d.default.func,d.default.object]).isRequired,toggle:d.default.func.isRequired,areaId:d.default.number.isRequired,insertAfterElement:d.default.oneOfType([d.default.number,d.default.string]),insertAtBottom:d.default.bool},t.default=(0,m.inject)(["PopoverOptionSet"],function(e){return{PopoverOptionSetComponent:e}},function(){return"ElementEditor"})(v)},"./client/src/components/ColumnSize.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function i(e){return{actions:{reduxForm:(0,p.bindActionCreators)({autofill:g.autofill},e)}}}Object.defineProperty(t,"__esModule",{value:!0});var u=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),s=n(1),c=r(s),d=n(0),f=r(d),p=n(5),m=n(10),g=n(14),h=function(e){function t(e){o(this,t);var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.handleClick=n.handleClick.bind(n),n.handleChangeSize=n.handleChangeSize.bind(n),n.handleChangeOffset=n.handleChangeOffset.bind(n),n}return l(t,e),u(t,[{key:"getColSizeSource",value:function(){for(var e=[],t=1;t<=this.props.gridColumns;t++)e.push({label:"Column "+t+"/"+this.props.gridColumns,value:t});return e}},{key:"getOffsetSizeSource",value:function(){var e=[];e.push({label:"None",value:0});for(var t=1;t<=this.props.gridColumns;t++)e.push({label:"Column "+t+"/"+this.props.gridColumns,value:t});return e}},{key:"handleClick",value:function(e){e.stopPropagation()}},{key:"handleChangeSize",value:function(e){var t=this.props,n=t.elementId,r=t.defaultViewport;this.props.actions.reduxForm.autofill("element.ElementForm_"+n,"PageElements_"+n+"_Size"+r,e.target.value),this.props.handleChangeSize(e)}},{key:"handleChangeOffset",value:function(e){var t=this.props,n=t.elementId,r=t.defaultViewport;this.props.actions.reduxForm.autofill("element.ElementForm_"+n,"PageElements_"+n+"_Offset"+r,e.target.value),this.props.handleChangeOffset(e)}},{key:"render",value:function(){var e=[];e.push({label:"None",value:0});for(var t=1;t<=this.props.gridColumns;t++)e.push({label:"Column "+t+"/"+this.props.gridColumns,value:t});return c.default.createElement("div",null,c.default.createElement("hr",null),c.default.createElement("label",{className:"mb-0 font-italic",htmlFor:"colSize"},"Size ",this.props.defaultViewport,c.default.createElement("select",{defaultValue:this.props.size,onChange:this.handleChangeSize,onClick:this.handleClick,id:"colSize"},this.getColSizeSource().map(function(e){return c.default.createElement("option",{value:e.value},e.label)}))),c.default.createElement("label",{className:"mb-0 ml-2 font-italic",htmlFor:"colOffset"},"Offset ",this.props.defaultViewport,c.default.createElement("select",{defaultValue:this.props.offset,onChange:this.handleChangeOffset,onClick:this.handleClick,id:"colOffset"},this.getOffsetSizeSource().map(function(e){return c.default.createElement("option",{value:e.value},e.label)}))))}}]),t}(s.PureComponent);h.defaultProps={},h.propTypes={actions:f.default.shape({reduxFrom:f.default.object}),elementId:f.default.number,size:f.default.number,defaultViewport:f.default.string,offset:f.default.number,gridColumns:f.default.number,handleChangeSize:f.default.func,handleChangeOffset:f.default.func},t.default=(0,p.compose)((0,m.connect)(function(){},i))(h)},"./client/src/components/Element.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function a(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function l(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function i(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function u(e,t){var n=t.element.id,r=(0,C.loadElementFormStateName)(n),o=(0,j.loadElementSchemaValue)("schemaUrl",n),a=function(e){return"Tabs"===e.component},l=e.form&&e.form.formSchemas[o]&&e.form.formSchemas[o].schema&&e.form.formSchemas[o].schema.fields.find(a),i=l&&l.id,u="element."+r+"__"+i;return{tabSetName:i,activeTab:e.tabs&&e.tabs.fields&&e.tabs.fields[u]&&e.tabs.fields[u].activeTab}}function s(e,t){var n=(0,C.loadElementFormStateName)(t.element.id);return{onActivateTab:function(t,r){e(S.activateTab("element."+n+"__"+t,r))}}}Object.defineProperty(t,"__esModule",{value:!0}),t.Component=void 0;var c=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),d=n(1),f=r(d),p=n(0),m=r(p),g=n("./client/src/types/elementType.js"),h=n("./client/src/types/elementTypeType.js"),b=n(5),v=n(2),y=n(7),E=r(y),T=n(6),O=r(T),_=n(10),C=n("./client/src/state/editor/loadElementFormStateName.js"),j=n("./client/src/state/editor/loadElementSchemaValue.js"),k=n(15),S=function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n]);return t.default=e,t}(k),D=n(4),w=n(12),P=n("./client/src/lib/dragHelpers.js"),A=function(e){function t(e){a(this,t);var n=l(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.handleKeyUp=n.handleKeyUp.bind(n),n.handleExpand=n.handleExpand.bind(n),n.handleLoadingError=n.handleLoadingError.bind(n),n.handleTabClick=n.handleTabClick.bind(n),n.updateFormTab=n.updateFormTab.bind(n),n.handleChangeSize=n.handleChangeSize.bind(n),n.handleChangeOffset=n.handleChangeOffset.bind(n),n.state={previewExpanded:!1,initialTab:"",loadingError:!1,childRenderingError:!1,size:e.element.blockSchema.grid.column.size,offset:e.element.blockSchema.grid.column.offset},n}return i(t,e),c(t,null,[{key:"getDerivedStateFromError",value:function(){return{childRenderingError:!0}}}]),c(t,[{key:"componentDidMount",value:function(){var e=this.props.connectDragPreview;e&&e((0,w.getEmptyImage)(),{captureDraggingState:!0})}},{key:"getVersionedStateClassName",value:function(){var e=this.props.element;return e.isPublished?e.isPublished&&!e.isLiveVersion?"element-editor__element--modified":"element-editor__element--published":"element-editor__element--draft"}},{key:"getColumnSizeClassNames",value:function(){var e,t=this.props.element;return e={},o(e,"col-lg-"+this.state.size,!0),o(e,"offset-lg-"+this.state.offset,!0),o(e,"is-row",!0===t.blockSchema.grid.isRow),o(e,"is-dragged-top",this.props.isDraggedOver&&"top"===this.props.isDraggedOverPosition),o(e,"is-dragged-bottom",this.props.isDraggedOver&&"bottom"===this.props.isDraggedOverPosition),e}},{key:"handleLoadingError",value:function(){this.setState({loadingError:!0})}},{key:"updateFormTab",value:function(e){var t=this.props,n=t.tabSetName,r=t.onActivateTab,o=this.state.initialTab;o||this.setState({initialTab:e}),e||o?r(n,e||o):r(n,"Main")}},{key:"handleTabClick",value:function(e){var t=this.props.activeTab,n=this.state.loadingError;e===t||n||(this.setState({previewExpanded:!0}),this.updateFormTab(e))}},{key:"handleExpand",value:function(e){var t=this.props,n=t.type,r=t.link,o=this.state.loadingError;return"button"===e.target.type?void e.stopPropagation():n.inlineEditable&&!o?void this.setState({previewExpanded:!this.state.previewExpanded}):void(window.location=r)}},{key:"handleKeyUp",value:function(e){var t=e.target.nodeName;" "!==e.key&&"Enter"!==e.key||["input","textarea"].includes(t.toLowerCase())||this.handleExpand(e)}},{key:"handleChangeSize",value:function(e){this.setState({size:e.target.value})}},{key:"handleChangeOffset",value:function(e){this.setState({offset:e.target.value})}},{key:"render",value:function(){var e=this,t=this.props,n=t.element,r=t.type,o=t.areaId,a=t.HeaderComponent,l=t.ContentComponent,i=t.ColumnSizeComponent,u=t.link,s=t.activeTab,c=t.connectDragSource,d=t.connectDropTarget,p=t.isDragging,m=t.isOver,g=t.onDragEnd,h=this.state,b=h.childRenderingError,v=h.previewExpanded;if(!n.id)return null;var y=E.default.inject(E.default._t("ElementalElement.TITLE","Edit this {type} block"),{type:r.title}),T=(0,O.default)("element-editor__element",{"element-editor__element--expandable":r.inlineEditable,"element-editor__element--expanded":v,"element-editor__element--dragging":p,"element-editor__element--dragged-over":m},this.getVersionedStateClassName(),this.getColumnSizeClassNames()),_=d(f.default.createElement("div",{className:T,onClick:this.handleExpand,onKeyUp:this.handleKeyUp,role:"button",tabIndex:0,title:y,key:n.id},f.default.createElement(a,{element:n,type:r,areaId:o,expandable:r.inlineEditable,link:u,previewExpanded:v&&!b,handleEditTabsClick:this.handleTabClick,activeTab:s,disableTooltip:p,onDragEnd:g}),!b&&f.default.createElement(l,{id:n.id,fileUrl:n.blockSchema.fileURL,fileTitle:n.blockSchema.fileTitle,content:n.blockSchema.content,previewExpanded:v&&!p,activeTab:s,onFormInit:function(){return e.updateFormTab(s)},handleLoadingError:this.handleLoadingError}),b&&f.default.createElement("div",{className:"alert alert-danger mt-2"},E.default._t("ElementalElement.CHILD_RENDERING_ERROR","Something went wrong with this block. Please try saving and refreshing the CMS.")),!n.blockSchema.grid.isRow&&f.default.createElement(i,{elementId:n.id,size:n.blockSchema.grid.column.size,defaultViewport:n.blockSchema.grid.column.defaultViewport,gridColumns:n.blockSchema.grid.gridColumns,offset:n.blockSchema.grid.column.offset,handleChangeSize:this.handleChangeSize,handleChangeOffset:this.handleChangeOffset})));return p?_:v?_:c(_)}}]),t}(d.Component);A.propTypes={element:g.elementType,type:h.elementTypeType.isRequired,areaId:m.default.number.isRequired,link:m.default.string.isRequired,activeTab:m.default.string,tabSetName:m.default.string,onActivateTab:m.default.func,connectDragSource:m.default.func.isRequired,connectDragPreview:m.default.func.isRequired,connectDropTarget:m.default.func.isRequired,isDragging:m.default.bool.isRequired,isOver:m.default.bool.isRequired,onDragOver:m.default.func,onDragEnd:m.default.func,onDragStart:m.default.func,isDraggedOver:m.default.bool,isDraggedOverPosition:m.default.string},A.defaultProps={element:null},t.Component=A;var I={drop:function(e,t,n){var r=e.element;return{target:r.id,dropSpot:(0,P.isOverTop)(t,n,r)?"top":"bottom"}},hover:function(e,t,n){var r=e.element,o=e.onDragOver;o&&o(r,(0,P.isOverTop)(t,n,r))}};t.default=(0,b.compose)((0,D.DropTarget)("element",I,function(e,t){return{connectDropTarget:e.dropTarget(),isOver:t.isOver()}}),(0,D.DragSource)("element",P.elementDragSource,function(e,t){return{connectDragSource:e.dragSource(),connectDragPreview:e.dragPreview(),isDragging:t.isDragging()}}),(0,_.connect)(u,s),(0,v.inject)(["ElementHeader","ElementContent","ColumnSize"],function(e,t,n){return{HeaderComponent:e,ContentComponent:t,ColumnSizeComponent:n}},function(){return"ElementEditor.ElementList.Element"}))(A)},"./client/src/components/ElementEditor/Toolbar.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),u=n(1),s=r(u),c=n(0),d=r(c),f=n(2),p=n("./client/src/types/elementTypeType.js"),m=n(4),g=function(e){function t(){return o(this,t),a(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return l(t,e),i(t,[{key:"render",value:function(){var e=this.props,t=e.AddBlockToBottomButton,n=e.AddBlockToTopButton,r=e.elementTypes,o=e.areaId;return(0,e.connectDropTarget)(s.default.createElement("div",{className:"element-editor__toolbar"},s.default.createElement(t,{elementTypes:r,areaId:o}),s.default.createElement(n,{elementTypes:r,areaId:o})))}}]),t}(u.PureComponent);g.defaultProps={},g.propTypes={elementTypes:d.default.arrayOf(p.elementTypeType).isRequired,areaId:d.default.number.isRequired,AddBlockToBottomButton:d.default.oneOfType([d.default.node,d.default.func]).isRequired,AddBlockToTopButton:d.default.oneOfType([d.default.node,d.default.func]).isRequired,connectDropTarget:d.default.func.isRequired,onDragOver:d.default.func,onDragDrop:d.default.func};var h={hover:function(e){var t=e.onDragOver;t&&t()}};t.default=(0,m.DropTarget)("element",h,function(e){return{connectDropTarget:e.dropTarget()}})((0,f.inject)(["AddBlockToBottomButton","AddBlockToTopButton"],function(e,t){return{AddBlockToBottomButton:e,AddBlockToTopButton:t}},function(){return"ElementEditor.ElementToolbar"})(g))},"./client/src/components/ElementList.js":function(e,t,n){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0}),t.Component=void 0;var i=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},u=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),s=n(1),c=r(s),d=n(0),f=r(d),p=n("./client/src/types/elementType.js"),m=n("./client/src/types/elementTypeType.js"),g=n(5),h=n(2),b=n(6),v=r(b),y=n(7),E=r(y),T=n(4),O=n("./client/src/lib/dragHelpers.js"),_=n("./client/src/state/editor/elementConfig.js"),C=function(e){function t(){return o(this,t),a(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return l(t,e),u(t,[{key:"getDragIndicatorIndex",value:function(){var e=this.props,t=e.dragTargetElementId,n=e.draggedItem,r=e.blocks,o=e.dragSpot;return(0,O.getDragIndicatorIndex)(r.map(function(e){return e.id}),t,n&&n.id,o)}},{key:"renderBlocks",value:function(){var e=this,t=this.props,n=t.ElementComponent,r=t.HoverBarComponent,o=t.blocks,a=t.allowedElementTypes,l=t.elementTypes,i=t.areaId,u=t.onDragEnd,s=t.onDragOver,d=t.onDragStart,f=t.isDraggingOver;if(!o)return null;if(o&&!o.length)return c.default.createElement("div",null,E.default._t("ElementList.ADD_BLOCKS","Add blocks to place your content"));var p=o.map(function(t){return c.default.createElement(n,{key:t.id,element:t,areaId:i,type:(0,_.getElementTypeConfig)(t.blockSchema.typeName,l),link:t.blockSchema.actions.edit,onDragOver:s,onDragEnd:u,onDragStart:d,isDraggedOver:e.props.dragTargetElementId===t.id&&e.props.draggedItem&&e.props.draggedItem.id!==t.id,isDraggedOverPosition:e.props.dragSpot},f||c.default.createElement(r,{key:"create-after-"+t.id,areaId:i,elementId:t.id,elementTypes:a}))});return f||(p=[c.default.createElement(r,{key:0,areaId:i,elementId:0,elementTypes:a})].concat(p)),p}},{key:"renderLoading",value:function(){var e=this.props,t=e.loading,n=e.LoadingComponent;return t?c.default.createElement(n,null):null}},{key:"render",value:function(){var e=this.props.blocks,t=(0,v.default)("elemental-editor-list","row",{"elemental-editor-list--empty":!e||!e.length});return this.props.connectDropTarget(c.default.createElement("div",{className:t},this.renderLoading(),this.renderBlocks()))}}]),t}(s.Component);C.propTypes={blocks:f.default.arrayOf(p.elementType),elementTypes:f.default.arrayOf(m.elementTypeType).isRequired,allowedElementTypes:f.default.arrayOf(m.elementTypeType).isRequired,loading:f.default.bool,areaId:f.default.number.isRequired,dragTargetElementId:f.default.oneOfType([f.default.string,f.default.bool]),onDragOver:f.default.func,onDragStart:f.default.func,onDragEnd:f.default.func},C.defaultProps={blocks:[],loading:!1},t.Component=C;var j={drop:function(e,t){var n=e.blocks,r=t.getDropResult();if(!r)return{};var o=(0,O.getDragIndicatorIndex)(n.map(function(e){return e.id}),r.target,t.getItem(),r.dropSpot),a=n[o-1]?n[o-1].id:"0";return i({},r,{dropAfterID:a})}};t.default=(0,g.compose)((0,T.DropTarget)("element",j,function(e,t){return{connectDropTarget:e.dropTarget(),draggedItem:t.getItem()}}),(0,h.inject)(["Element","Loading","HoverBar","DragPositionIndicator"],function(e,t,n,r){return{ElementComponent:e,LoadingComponent:t,HoverBarComponent:n,DragIndicatorComponent:r}},function(){return"ElementEditor.ElementList"}))(C)},"./client/src/lib/dragHelpers.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.elementDragSource=t.getDragIndicatorIndex=t.isOverTop=void 0;var r=n(13);t.isOverTop=function(e,t){var n=e.getClientOffset(),o=(0,r.findDOMNode)(t).getBoundingClientRect();return!0===t.props.element.blockSchema.grid.isRow?n.y<o.y+o.height/2:n.x<o.x+o.width/2},t.getDragIndicatorIndex=function(e,t,n,r){if(null===t||!n)return null;var o=t?e.findIndex(function(e){return e===t}):0,a=e.findIndex(function(e){return e===n});return"bottom"===r&&(o+=1),a===o||a+1===o?null:o},t.elementDragSource={beginDrag:function(e){return e.element},endDrag:function(e,t){var n=e.onDragEnd,r=t.getDropResult();if(n&&r&&r.dropAfterID){var o=t.getItem().id,a=r.dropAfterID;o!==a&&n(o,a)}}}},"./client/src/state/editor/addElementMutation.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.config=t.mutation=void 0;var r=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},o=function(e,t){return Object.freeze(Object.defineProperties(e,{raw:{value:Object.freeze(t)}}))}(["\nmutation AddElementToArea($className: String!, $elementalAreaID: ID!, $afterElementID: ID, $insertAtBottom: Boolean) {\n  addElementToArea(\n    className: $className,\n    elementalAreaID: $elementalAreaID,\n    afterElementID: $afterElementID,\n    insertAtBottom: $insertAtBottom\n  ) {\n    id\n  }\n}\n"],["\nmutation AddElementToArea($className: String!, $elementalAreaID: ID!, $afterElementID: ID, $insertAtBottom: Boolean) {\n  addElementToArea(\n    className: $className,\n    elementalAreaID: $elementalAreaID,\n    afterElementID: $afterElementID,\n    insertAtBottom: $insertAtBottom\n  ) {\n    id\n  }\n}\n"]),a=n(9),l=n(8),i=function(e){return e&&e.__esModule?e:{default:e}}(l),u=n("./client/src/state/editor/readBlocksForAreaQuery.js"),s=(0,i.default)(o),c={props:function(e){var t=e.mutate,n=e.ownProps,o=n.actions,a=n.areaId;return{actions:r({},o,{handleAddElementToArea:function(e,n,r){return t({variables:{className:e,elementalAreaID:a,afterElementID:n,insertAtBottom:r}})}})}},options:function(e){var t=e.areaId;return{refetchQueries:[{query:u.query,variables:u.config.options({areaId:t}).variables}]}}};t.mutation=s,t.config=c,t.default=(0,a.graphql)(s,c)},"./client/src/state/editor/elementConfig.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.getElementTypeConfig=t.getConfig=void 0;var r=n(3),o=function(e){return e&&e.__esModule?e:{default:e}}(r),a=t.getConfig=function(){return o.default.getSection("DNADesign\\Elemental\\Controllers\\ElementalAreaController")};t.getElementTypeConfig=function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;return(Array.isArray(t)?t:a().elementTypes).find(function(t){return t.class===e||t.name===e})}},"./client/src/state/editor/loadElementFormStateName.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.loadElementFormStateName=void 0;var r=n(3),o=function(e){return e&&e.__esModule?e:{default:e}}(r);t.loadElementFormStateName=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,t=o.default.getSection("DNADesign\\Elemental\\Controllers\\ElementalAreaController"),n=t.form.elementForm.formNameTemplate;return e?n.replace("{id}",e):n}},"./client/src/state/editor/loadElementSchemaValue.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.loadElementSchemaValue=void 0;var r=n(3),o=function(e){return e&&e.__esModule?e:{default:e}}(r);t.loadElementSchemaValue=function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,n=o.default.getSection("DNADesign\\Elemental\\Controllers\\ElementalAreaController"),r=n.form.elementForm[e]||"";return t?r+"/"+t:r}},"./client/src/state/editor/readBlocksForAreaQuery.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.config=t.query=void 0;var r=function(e,t){return Object.freeze(Object.defineProperties(e,{raw:{value:Object.freeze(t)}}))}(["\nquery ReadBlocksForArea($id:ID!) {\n  readOneElementalArea(filter: { id: { eq: $id } }, versioning: {\n    mode: DRAFT\n  }){\n    elements {\n      id\n      title\n      blockSchema\n      obsoleteClassName\n      isLiveVersion\n      isPublished\n      version\n      canCreate\n      canPublish\n      canUnpublish\n      canDelete\n    }\n  }\n}\n"],["\nquery ReadBlocksForArea($id:ID!) {\n  readOneElementalArea(filter: { id: { eq: $id } }, versioning: {\n    mode: DRAFT\n  }){\n    elements {\n      id\n      title\n      blockSchema\n      obsoleteClassName\n      isLiveVersion\n      isPublished\n      version\n      canCreate\n      canPublish\n      canUnpublish\n      canDelete\n    }\n  }\n}\n"]),o=n(9),a=n(8),l=function(e){return e&&e.__esModule?e:{default:e}}(a),i=(0,l.default)(r),u={options:function(e){return{variables:{id:e.areaId}}},props:function(e){var t=e.data,n=t.error,r=t.readOneElementalArea,o=t.loading,a=null;r&&(a=r.elements);var l=n&&n.graphQLErrors&&n.graphQLErrors.map(function(e){return e.message});return{loading:o||!a,blocks:a,graphQLErrors:l}}};t.query=i,t.config=u,t.default=(0,o.graphql)(i,u)},"./client/src/types/elementType.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.elementType=void 0;var r=n(0),o=function(e){return e&&e.__esModule?e:{default:e}}(r),a=o.default.shape({id:o.default.string.isRequired,title:o.default.string,blockSchema:o.default.object,inlineEditable:o.default.bool,published:o.default.bool,liveVersion:o.default.bool,version:o.default.number});t.elementType=a},"./client/src/types/elementTypeType.js":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.elementTypeType=void 0;var r=n(0),o=function(e){return e&&e.__esModule?e:{default:e}}(r),a=o.default.shape({name:o.default.string,title:o.default.string,icon:o.default.string,inlineEditable:o.default.boolean,editTabs:o.default.arrayOf(o.default.shape({title:o.default.string,name:o.default.string})),config:o.default.object});t.elementTypeType=a},0:function(e,t){e.exports=PropTypes},1:function(e,t){e.exports=React},10:function(e,t){e.exports=ReactRedux},11:function(e,t){e.exports=Reactstrap},12:function(e,t){e.exports=ReactDNDHtml5Backend},13:function(e,t){e.exports=ReactDom},14:function(e,t){e.exports=ReduxForm},15:function(e,t){e.exports=TabsActions},2:function(e,t){e.exports=Injector},3:function(e,t){e.exports=Config},4:function(e,t){e.exports=ReactDND},5:function(e,t){e.exports=Redux},6:function(e,t){e.exports=classnames},7:function(e,t){e.exports=i18n},8:function(e,t){e.exports=GraphQLTag},9:function(e,t){e.exports=ReactApollo}});