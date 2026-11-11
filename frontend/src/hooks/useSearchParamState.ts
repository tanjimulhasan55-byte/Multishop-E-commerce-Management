import { useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';

/**
 * Read/write a group of URL search params, so filters are shareable and
 * survive reloads. Setting a value to '' / undefined removes it. Any change
 * other than `page` resets pagination.
 */
export function useSearchParamState<K extends string>(keys: readonly K[]) {
  const [params, setParams] = useSearchParams();

  const values = Object.fromEntries(keys.map((k) => [k, params.get(k) ?? ''])) as Record<K, string>;

  const update = useCallback(
    (patch: Partial<Record<K, string | number | undefined>>) => {
      setParams(
        (prev) => {
          const next = new URLSearchParams(prev);
          for (const [k, v] of Object.entries(patch) as [K, string | number | undefined][]) {
            if (v === undefined || v === '') next.delete(k);
            else next.set(k, String(v));
          }
          if (!('page' in patch)) next.delete('page');
          return next;
        },
        { replace: true },
      );
    },
    [setParams],
  );

  const page = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);

  return { values, update, page, setPage: (p: number) => update({ page: p } as Partial<Record<K, number>>) };
}
