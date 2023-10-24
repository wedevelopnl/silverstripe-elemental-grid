import React from 'react';
import Injector from 'lib/Injector';
import ElementList from 'components/ElementList';
import Header from 'components/Header';
import Element from 'components/Element';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';
import addElementToArea from 'state/editor/addElementMutation';
import AddElementPopover from 'components/AddElementPopover';

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

const OverruledAddElementPopover = () => (props) => (
  <div>
    <AddElementPopover {...props} />
  </div>
);

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementList,
    ElementHeader: Header,
    Element,
    ColumnSize,
    AddBlockToBottomButton,
    AddBlockToTopButton,
  }, { force: true });

  Injector.transform('toolbar-override', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
  });

  Injector.transform(
    'cms-element-adder-override',
    (updater) => {
      // Add GraphQL query for adding elements to an ElementEditor (ElementalArea)
      updater.component(
        'AddElementPopover',
        addElementToArea,
        'ElementAddButton'
      );
    }
  );

  Injector.transform('popover-override', (updater) => {
    updater.component('AddElementPopover', OverruledAddElementPopover);
  });
});
