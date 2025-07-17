import React from 'react';
import Injector from 'lib/Injector';
import ColumnSize from 'components/ColumnSize';
import AddBlockToBottomButton from 'components/AddBlockToBottomButton';
import AddBlockToTopButton from 'components/AddBlockToTopButton';
import Toolbar from 'components/ElementEditor/Toolbar';

const OverruledToolbar = () => (props) => (
  <div>
    <Toolbar {...props} />
  </div>
);

window.document.addEventListener('DOMContentLoaded', () => {
  // Register buttons directly so toolbar inject() can find them
  Injector.component.register('AddBlockToBottomButton', AddBlockToBottomButton);
  Injector.component.register('AddBlockToTopButton', AddBlockToTopButton);
  Injector.component.register('ColumnSize', ColumnSize);

  // Register the toolbar that uses the buttons
  Injector.transform('elemental-grid-toolbar', (updater) => {
    updater.component('ElementToolbar', OverruledToolbar);
  });
});
