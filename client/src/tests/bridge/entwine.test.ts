import { createElement } from 'react';
import { vi } from 'vitest';

interface EntwineRules {
  onmatch?(this: unknown): void;
  onunmatch?(this: unknown): void;
  [key: string]: unknown;
}

const mockLoadComponent = vi.fn();
vi.mock('@/bridge/Injector', () => ({
  loadComponent: mockLoadComponent,
}));

const MockGridEditorErrorBoundary = vi.fn(({ children }) => children);
vi.mock('@/components/GridEditorErrorBoundary/GridEditorErrorBoundary', () => ({
  default: MockGridEditorErrorBoundary,
}));

const MockGridQueryProvider = vi.fn(({ children }) => children);
vi.mock('@/hooks/QueryProvider', () => ({
  default: MockGridQueryProvider,
}));

const mockRoot = { render: vi.fn(), unmount: vi.fn() };
const mockCreateRoot = vi.fn(() => mockRoot);
vi.mock('react-dom/client', () => ({
  createRoot: mockCreateRoot,
}));

describe('entwine bridge', () => {
  let capturedNamespace: string;
  let capturedSelector: string;
  let capturedRules: EntwineRules;

  beforeEach(async () => {
    vi.resetModules();
    mockLoadComponent.mockReset();
    MockGridEditorErrorBoundary.mockClear();
    MockGridQueryProvider.mockClear();
    mockCreateRoot.mockReset().mockReturnValue(mockRoot);
    mockRoot.render.mockReset();
    mockRoot.unmount.mockReset();

    const mockElement = {
      entwine: vi.fn((rules: EntwineRules) => {
        capturedRules = rules;
      }),
    };

    const mockJQuery = vi.fn((selector: string) => {
      capturedSelector = selector;
      return mockElement;
    }) as unknown as typeof window.jQuery;

    mockJQuery.entwine = vi.fn(
      (namespace: string, callback: ($: typeof window.jQuery) => void) => {
        capturedNamespace = namespace;
        callback(mockJQuery);
      },
    );

    window.jQuery = mockJQuery;

    await import('@/bridge/entwine');
  });

  it('registers entwine handlers in the "ss" namespace', () => {
    expect(capturedNamespace).toBe('ss');
  });

  it('targets the .js-injector-boot .grid-editor__container selector', () => {
    expect(capturedSelector).toBe('.js-injector-boot .grid-editor__container');
  });

  it('onmatch loads GridEditor, reads schema, and renders React tree', () => {
    const MockGridEditor = vi.fn();
    mockLoadComponent.mockReturnValue(MockGridEditor);

    const domElement = document.createElement('div');
    const setReactRoot = vi.fn();

    const context = {
      data: vi.fn().mockReturnValue({ 'grid-page-id': 7 }),
      setReactRoot,
      0: domElement,
    };

    capturedRules.onmatch!.call(context as never);

    expect(mockLoadComponent).toHaveBeenCalledWith('GridEditor');
    expect(context.data).toHaveBeenCalledWith('schema');
    expect(mockCreateRoot).toHaveBeenCalledWith(domElement);
    expect(setReactRoot).toHaveBeenCalledWith(mockRoot);
    expect(mockRoot.render).toHaveBeenCalledWith(
      createElement(
        MockGridQueryProvider,
        null,
        createElement(
          MockGridEditorErrorBoundary,
          null,
          createElement(MockGridEditor, { pageId: 7 }),
        ),
      ),
    );
  });

  it('onunmatch unmounts and cleans up the React root', () => {
    const setReactRoot = vi.fn();
    const context = {
      getReactRoot: vi.fn().mockReturnValue(mockRoot),
      setReactRoot,
    };

    capturedRules.onunmatch!.call(context as never);

    expect(mockRoot.unmount).toHaveBeenCalled();
    expect(setReactRoot).toHaveBeenCalledWith(null);
  });

  it('onunmatch does nothing when root is null', () => {
    const setReactRoot = vi.fn();
    const context = {
      getReactRoot: vi.fn().mockReturnValue(null),
      setReactRoot,
    };

    capturedRules.onunmatch!.call(context as never);

    expect(mockRoot.unmount).not.toHaveBeenCalled();
    expect(setReactRoot).not.toHaveBeenCalled();
  });

  it('onmatch warns when schema has wrong types', () => {
    mockLoadComponent.mockReturnValue(vi.fn());
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    const context = {
      data: vi.fn().mockReturnValue({ 'grid-page-id': 'not-a-number' }),
      setReactRoot: vi.fn(),
      0: document.createElement('div'),
    };

    expect(() => capturedRules.onmatch!.call(context as never)).not.toThrow();
    expect(warnSpy).toHaveBeenCalledWith(
      '[GridEditor] Failed to mount grid editor.',
      expect.any(Error),
    );
    expect(mockRoot.render).not.toHaveBeenCalled();

    warnSpy.mockRestore();
  });

  it('onmatch warns when schema has missing keys', () => {
    mockLoadComponent.mockReturnValue(vi.fn());
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    const context = {
      data: vi.fn().mockReturnValue({}),
      setReactRoot: vi.fn(),
      0: document.createElement('div'),
    };

    expect(() => capturedRules.onmatch!.call(context as never)).not.toThrow();
    expect(warnSpy).toHaveBeenCalledWith(
      '[GridEditor] Failed to mount grid editor.',
      expect.any(Error),
    );
    expect(mockRoot.render).not.toHaveBeenCalled();

    warnSpy.mockRestore();
  });

  it('onmatch catches loadComponent failures and warns', () => {
    const error = new TypeError('Injector not available');
    mockLoadComponent.mockImplementation(() => { throw error; });
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    const context = {
      data: vi.fn().mockReturnValue({ 'grid-page-id': 7 }),
      setReactRoot: vi.fn(),
      0: document.createElement('div'),
    };

    expect(() => capturedRules.onmatch!.call(context as never)).not.toThrow();
    expect(warnSpy).toHaveBeenCalledWith(
      '[GridEditor] Failed to mount grid editor.',
      error,
    );

    warnSpy.mockRestore();
  });
});
