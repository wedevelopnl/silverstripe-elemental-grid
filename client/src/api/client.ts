import { getSecurityId } from './config';
import { ApiError } from './errors';

/**
 * Perform a GET request to a CMS API endpoint.
 *
 * @throws ApiError on non-OK HTTP status
 */
export async function apiGet<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    throw new ApiError(response.status, response.statusText);
  }

  return response.json() as Promise<T>;
}

/**
 * Perform a POST request to a CMS API endpoint with JSON body.
 * Automatically includes the X-SecurityID CSRF header.
 *
 * @throws ApiError on non-OK HTTP status
 */
export async function apiPost(url: string, body: object): Promise<void> {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-SecurityID': getSecurityId(),
    },
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    throw new ApiError(response.status, response.statusText);
  }
}
