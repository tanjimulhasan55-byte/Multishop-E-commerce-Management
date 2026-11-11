import type {
  CollectionDocument,
  Flat,
  Paginated,
  Resource,
  ResourceIdentifier,
  SingleDocument,
} from '@/types/api';
import type { Translations } from '@/types/models';

/** Flatten a JSON:API resource to `{ id, ...attributes }`. */
export function flatten<A>(resource: Resource<A>): Flat<A> {
  return { id: resource.id, ...resource.attributes };
}

function findIncluded(included: Resource[] | undefined, ref: ResourceIdentifier): Resource | undefined {
  return included?.find((r) => r.type === ref.type && r.id === ref.id);
}

/**
 * Flatten a resource and resolve its to-one relationships from `included`
 * (e.g. `?include=shop` → `listing.shop`).
 */
export function flattenWithIncluded<A, R extends object = object>(
  resource: Resource<A>,
  included?: Resource[],
): Flat<A> & R {
  const out: Record<string, unknown> = { ...flatten(resource) };
  for (const [name, rel] of Object.entries(resource.relationships ?? {})) {
    if (rel.data && !Array.isArray(rel.data)) {
      const hit = findIncluded(included, rel.data);
      if (hit) out[name] = flatten(hit);
    }
  }
  return out as Flat<A> & R;
}

export function unwrapOne<A, R extends object = object>(doc: SingleDocument<A>): Flat<A> & R {
  return flattenWithIncluded<A, R>(doc.data, doc.included);
}

export function unwrapMany<A, R extends object = object>(doc: CollectionDocument<A>): Paginated<Flat<A> & R> {
  const items = doc.data.map((r) => flattenWithIncluded<A, R>(r, doc.included));
  const meta = doc.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: items.length,
    total: items.length,
    from: items.length ? 1 : null,
    to: items.length,
  };
  return { items, meta };
}

/** Normalise a translation map (Laravel sends `[]` when empty). */
export function asTranslations(value: unknown): Translations {
  if (!value || Array.isArray(value) || typeof value !== 'object') return {};
  const v = value as Record<string, unknown>;
  return {
    en: typeof v.en === 'string' ? v.en : undefined,
    bn: typeof v.bn === 'string' ? v.bn : undefined,
  };
}
