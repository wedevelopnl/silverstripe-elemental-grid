import { apiDelete, apiGet, apiPatch, apiPost } from '@/api/client';
import { ApiError } from '@/api/errors';

vi.mock('@/api/config', () => ({
  getSecurityId: () => 'mock-token',
}));

describe('apiGet', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('returns parsed JSON on success', async () => {
    const payload = { foo: 'bar' };
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(payload),
      }),
    );

    const result = await apiGet<typeof payload>('/test');

    expect(result).toEqual(payload);
    expect(fetch).toHaveBeenCalledWith('/test', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
  });

  it('throws ApiError on non-OK response', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 404,
        statusText: 'Not Found',
      }),
    );

    await expect(apiGet('/missing')).rejects.toThrow(ApiError);
    await expect(apiGet('/missing')).rejects.toThrow('API error 404');
  });
});

describe.each([
  { name: 'apiPost', fn: apiPost, method: 'POST' },
  { name: 'apiPatch', fn: apiPatch, method: 'PATCH' },
  { name: 'apiDelete', fn: apiDelete, method: 'DELETE' },
])('$name', ({ fn, method }) => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it(`sends ${method} with JSON body and security header`, async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({ ok: true }),
    );

    await fn('/action', { id: 42 });

    expect(fetch).toHaveBeenCalledWith('/action', {
      method,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-SecurityID': 'mock-token',
      },
      body: JSON.stringify({ id: 42 }),
    });
  });

  it('throws ApiError on non-OK response', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 403,
        statusText: 'Forbidden',
      }),
    );

    await expect(fn('/action', {})).rejects.toThrow(ApiError);
    await expect(fn('/action', {})).rejects.toThrow('403');
  });

  it('resolves to void on success', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({ ok: true }),
    );

    const result = await fn('/action', { id: 1 });

    expect(result).toBeUndefined();
  });
});
