import { render, screen } from '@testing-library/react';
import { vi } from 'vitest';

import GridEditorErrorBoundary from '@/components/GridEditorErrorBoundary/GridEditorErrorBoundary';

vi.mock('@/utils/toast', () => ({
  showToast: vi.fn(),
}));

function ThrowingChild(): never {
  throw new Error('render explosion');
}

function GoodChild() {
  return <p>All fine</p>;
}

describe('GridEditorErrorBoundary', () => {
  let errorSpy: ReturnType<typeof vi.spyOn>;
  const suppressJsdomErrors = (event: ErrorEvent) => event.preventDefault();

  beforeEach(async () => {
    errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    window.addEventListener('error', suppressJsdomErrors);
    const mod = await import('@/utils/toast');
    vi.mocked(mod.showToast).mockClear();
  });

  afterEach(() => {
    window.removeEventListener('error', suppressJsdomErrors);
    errorSpy.mockRestore();
  });

  it('renders children normally when no error occurs', () => {
    render(
      <GridEditorErrorBoundary>
        <GoodChild />
      </GridEditorErrorBoundary>,
    );

    expect(screen.getByText('All fine')).toBeDefined();
  });

  it('renders fallback UI when a child throws during render', () => {
    render(
      <GridEditorErrorBoundary>
        <ThrowingChild />
      </GridEditorErrorBoundary>,
    );

    expect(
      screen.getByText(
        'The grid editor failed to render. Try reloading the page.',
      ),
    ).toBeDefined();
  });

  it('calls showToast with error type when a child throws', async () => {
    const { showToast } = await import('@/utils/toast');

    render(
      <GridEditorErrorBoundary>
        <ThrowingChild />
      </GridEditorErrorBoundary>,
    );

    expect(showToast).toHaveBeenCalledWith(
      'The grid editor encountered an error and could not render.',
    );
  });

  it('logs the error to console.error', () => {
    render(
      <GridEditorErrorBoundary>
        <ThrowingChild />
      </GridEditorErrorBoundary>,
    );

    expect(errorSpy).toHaveBeenCalledWith(
      '[GridEditor] Render error:',
      expect.any(Error),
      expect.objectContaining({ componentStack: expect.any(String) }),
    );
  });
});
