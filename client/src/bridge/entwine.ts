import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import GridEditorErrorBoundary from '@/components/GridEditorErrorBoundary/GridEditorErrorBoundary';
import GridQueryProvider from '@/hooks/QueryProvider';
import { loadComponent } from './Injector';

/**
 * jQuery entwine bridge that mounts the React grid editor inside CMS pages.
 *
 * Entwine's onmatch/onunmatch hooks fire automatically when the CMS
 * replaces page content via AJAX navigation, handling mount/unmount
 * without explicit lifecycle management.
 */
window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .grid-editor__container').entwine({
    onmatch() {
      try {
        const GridEditor = loadComponent('GridEditor');
        const schema = this.data('schema') as Record<string, unknown>;
        const areaId = schema['grid-area-id'] as number;
        const pageId = (schema['grid-page-id'] as number | null) ?? null;

        const root = createRoot(this[0]);
        this.setReactRoot(root);
        root.render(
          createElement(
            GridQueryProvider,
            null,
            createElement(
              GridEditorErrorBoundary,
              null,
              createElement(GridEditor, { areaId, pageId }),
            ),
          ),
        );
      } catch (error: unknown) {
        console.warn('[GridEditor] Failed to mount grid editor.', error);
      }
    },

    onunmatch() {
      const root = this.getReactRoot();
      if (root !== null) {
        root.unmount();
        this.setReactRoot(null);
      }
    },
  });
});
