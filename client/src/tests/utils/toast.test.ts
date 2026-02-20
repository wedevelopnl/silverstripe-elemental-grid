import { vi } from 'vitest';

import { showToast } from '@/utils/toast';

describe('showToast', () => {
  let mockDispatch: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    mockDispatch = vi.fn();
    window.ss = {
      config: {} as typeof window.ss.config,
      store: { dispatch: mockDispatch as (action: unknown) => void },
    };
    vi.spyOn(crypto, 'randomUUID').mockReturnValue(
      '00000000-0000-0000-0000-000000000000',
    );
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('dispatches DISPLAY_TOAST to the admin Redux store', () => {
    showToast('Something went wrong');

    expect(mockDispatch).toHaveBeenCalledWith({
      type: 'DISPLAY_TOAST',
      payload: {
        id: 'toast-00000000-0000-0000-0000-000000000000',
        text: 'Something went wrong',
        type: 'error',
        stay: true,
      },
    });
  });

  it('falls back to console.warn when store is undefined', () => {
    window.ss = { config: {} as typeof window.ss.config };
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    showToast('Something went wrong');

    expect(warnSpy).toHaveBeenCalledWith(
      '[GridEditor] error: Something went wrong',
    );
    expect(mockDispatch).not.toHaveBeenCalled();
  });

  it('sets stay: true for error type', () => {
    showToast('Error occurred', 'error');

    expect(mockDispatch).toHaveBeenCalledWith(
      expect.objectContaining({
        payload: expect.objectContaining({ stay: true }),
      }),
    );
  });

  it('sets stay: true for warning type', () => {
    showToast('Be careful', 'warning');

    expect(mockDispatch).toHaveBeenCalledWith(
      expect.objectContaining({
        payload: expect.objectContaining({ type: 'warning', stay: true }),
      }),
    );
  });

  it('sets stay: false for success type', () => {
    showToast('All good', 'success');

    expect(mockDispatch).toHaveBeenCalledWith(
      expect.objectContaining({
        payload: expect.objectContaining({ type: 'success', stay: false }),
      }),
    );
  });

  it('generates unique toast IDs', () => {
    vi.mocked(crypto.randomUUID)
      .mockReturnValueOnce('aaaa-1111' as `${string}-${string}-${string}-${string}-${string}`)
      .mockReturnValueOnce('bbbb-2222' as `${string}-${string}-${string}-${string}-${string}`);

    showToast('First');
    showToast('Second');

    const firstId = mockDispatch.mock.calls[0][0].payload.id as string;
    const secondId = mockDispatch.mock.calls[1][0].payload.id as string;
    expect(firstId).toBe('toast-aaaa-1111');
    expect(secondId).toBe('toast-bbbb-2222');
  });
});
