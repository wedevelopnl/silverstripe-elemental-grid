import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';

import { showToast } from '@/utils/toast';

interface Props {
  readonly children: ReactNode;
}

interface State {
  hasError: boolean;
}

/**
 * Error boundary that catches render-time exceptions in the grid editor
 * React tree. Shows a toast notification and renders a static fallback
 * instead of crashing the entire CMS panel.
 */
export default class GridEditorErrorBoundary extends Component<Props, State> {
  override state: State = { hasError: false };

  static getDerivedStateFromError(): State {
    return { hasError: true };
  }

  override componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[GridEditor] Render error:', error, info);
    showToast('The grid editor encountered an error and could not render.');
  }

  override render(): ReactNode {
    if (this.state.hasError) {
      return (
        <p className="grid-editor__error">
          The grid editor failed to render. Try reloading the page.
        </p>
      );
    }

    return this.props.children;
  }
}
