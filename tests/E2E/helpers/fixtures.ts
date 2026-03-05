import type { APIRequestContext } from '@playwright/test';

/** Maps class FQCN → fixture identifier → database ID. */
export interface FixtureMap {
  [className: string]: {
    [identifier: string]: number;
  };
}

export interface FixtureLoadResponse {
  success: true;
  fixture: string;
  data: {
    pageId: number;
    pageUrl: string;
    fixtureMap: FixtureMap;
  };
}

interface FixtureErrorResponse {
  success: false;
  error: string;
}

interface FixtureResetResponse {
  success: true;
}

const FIXTURE_ENDPOINT = '/dev/grid-fixtures';

/**
 * Load a named fixture via the FixtureController endpoint.
 *
 * Resets existing E2E data and writes the fixture into the database.
 * Returns page ID, URL, and full fixture map for test navigation.
 */
export async function loadFixture(
  request: APIRequestContext,
  name: string,
): Promise<FixtureLoadResponse['data']> {
  const response = await request.post(`${FIXTURE_ENDPOINT}/load`, {
    form: { fixture: name },
  });

  const body = (await response.json()) as
    | FixtureLoadResponse
    | FixtureErrorResponse;

  if (!response.ok() || !body.success) {
    const error = 'error' in body ? body.error : `HTTP ${response.status()}`;
    throw new Error(`Failed to load fixture "${name}": ${error}`);
  }

  return body.data;
}

/**
 * Reset all E2E fixture data (removes pages with 'e2e-' URL prefix).
 */
export async function resetFixtures(
  request: APIRequestContext,
): Promise<void> {
  const response = await request.post(`${FIXTURE_ENDPOINT}/reset`);

  const body = (await response.json()) as
    | FixtureResetResponse
    | FixtureErrorResponse;

  if (!response.ok() || !body.success) {
    const error = 'error' in body ? body.error : `HTTP ${response.status()}`;
    throw new Error(`Failed to reset fixtures: ${error}`);
  }
}
