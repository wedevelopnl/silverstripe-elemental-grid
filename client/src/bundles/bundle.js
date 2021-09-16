import Injector from 'lib/Injector';
import ElementList from 'components/ElementList';
import Element from 'components/Element';
import ColumnSize from 'components/ColumnSize';
import InlineEditForm from 'components/InlineEditForm';

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementList,
    Element,
    ColumnSize,
    ElementInlineEditForm: InlineEditForm,
  }, { force: true })
});
