import {
  type ElementTreeResponse,
  elementTreeResponseSchema,
} from '@/types/elements';
import { apiGet, apiPost } from './client';
import { getControllerLink } from './config';

/**
 * Fetch the full element tree for a CMS page.
 * Response is validated through the Zod schema.
 */
export async function fetchElementTree(
  pageId: number,
): Promise<ElementTreeResponse> {
  const base = getControllerLink();
  const data = await apiGet<unknown>(`${base}/api/readTree/${pageId}`);

  return elementTreeResponseSchema.parse(data);
}

export interface CreateElementParams {
  elementClass: string;
  elementalAreaID: number;
  insertAfterElementID?: number;
}

export async function createElement(
  params: CreateElementParams,
): Promise<void> {
  const base = getControllerLink();
  await apiPost(`${base}/api/create`, params);
}

export async function publishElement(id: number): Promise<void> {
  const base = getControllerLink();
  await apiPost(`${base}/api/publish`, { id });
}

export async function unpublishElement(id: number): Promise<void> {
  const base = getControllerLink();
  await apiPost(`${base}/api/unpublish`, { id });
}

export async function deleteElement(id: number): Promise<void> {
  const base = getControllerLink();
  await apiPost(`${base}/api/delete`, { id });
}

export async function duplicateElement(id: number): Promise<void> {
  const base = getControllerLink();
  await apiPost(`${base}/api/duplicate`, { id });
}
