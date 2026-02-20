import { vi } from 'vitest';

const registerComponentsSpy = vi.fn();

vi.mock('@/boot/registerComponents', () => ({
  registerComponents: registerComponentsSpy,
}));

describe('boot/index', () => {
  let addEventListenerSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.resetModules();
    registerComponentsSpy.mockClear();
    addEventListenerSpy = vi.spyOn(document, 'addEventListener');
  });

  afterEach(() => {
    addEventListenerSpy.mockRestore();
  });

  it('registers a DOMContentLoaded event listener', async () => {
    await import('@/boot/index');

    expect(addEventListenerSpy).toHaveBeenCalledWith(
      'DOMContentLoaded',
      expect.any(Function),
    );
  });

  it('calls registerComponents when the callback fires', async () => {
    await import('@/boot/index');

    const callback = addEventListenerSpy.mock.calls.find(
      (call: [string, ...unknown[]]) => call[0] === 'DOMContentLoaded',
    )?.[1] as () => void;

    expect(callback).toBeDefined();
    callback();

    expect(registerComponentsSpy).toHaveBeenCalledTimes(1);
  });

  it('catches and warns when registerComponents throws', async () => {
    const error = new TypeError('Injector not available');
    registerComponentsSpy.mockImplementation(() => { throw error; });
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    await import('@/boot/index');

    const callback = addEventListenerSpy.mock.calls.find(
      (call: [string, ...unknown[]]) => call[0] === 'DOMContentLoaded',
    )?.[1] as () => void;

    expect(() => callback()).not.toThrow();
    expect(warnSpy).toHaveBeenCalledWith(
      '[GridEditor] Failed to register components.',
      error,
    );

    warnSpy.mockRestore();
  });
});
