import { ApiError, ConfigError } from '@/api/errors';

describe('ApiError', () => {
  it('extends Error', () => {
    const error = new ApiError(404, 'Not Found');
    expect(error).toBeInstanceOf(Error);
  });

  it('has correct name', () => {
    const error = new ApiError(500, 'Internal Server Error');
    expect(error.name).toBe('ApiError');
  });

  it('exposes status and statusText', () => {
    const error = new ApiError(403, 'Forbidden');
    expect(error.status).toBe(403);
    expect(error.statusText).toBe('Forbidden');
  });

  it('formats a descriptive message', () => {
    const error = new ApiError(400, 'Bad Request');
    expect(error.message).toBe('API error 400: Bad Request');
  });
});

describe('ConfigError', () => {
  it('extends Error', () => {
    const error = new ConfigError('missing config');
    expect(error).toBeInstanceOf(Error);
  });

  it('has correct name', () => {
    const error = new ConfigError('oops');
    expect(error.name).toBe('ConfigError');
  });

  it('preserves the message', () => {
    const error = new ConfigError('CMS not loaded');
    expect(error.message).toBe('CMS not loaded');
  });
});
