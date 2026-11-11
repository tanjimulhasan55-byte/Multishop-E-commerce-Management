import type { QueryParams } from '@/types/api';

/** Drop empty values so URLs stay clean and cache keys stay stable. */
export function cleanParams(params: QueryParams): Record<string, string | number> {
  const out: Record<string, string | number> = {};
  for (const [k, v] of Object.entries(params)) {
    if (v === undefined || v === null || v === '' || v === false) continue;
    out[k] = v === true ? 1 : v;
  }
  return out;
}
