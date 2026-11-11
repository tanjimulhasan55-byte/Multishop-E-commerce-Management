/* ---------- JSON:API envelope ---------- */

export interface ResourceIdentifier {
  id: string;
  type: string;
}

export interface Resource<A = Record<string, unknown>> extends ResourceIdentifier {
  attributes: A;
  relationships?: Record<string, { data: ResourceIdentifier | ResourceIdentifier[] | null }>;
}

export interface SingleDocument<A = Record<string, unknown>, M = Record<string, unknown>> {
  data: Resource<A>;
  included?: Resource[];
  meta?: M;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface CollectionDocument<A = Record<string, unknown>> {
  data: Resource<A>[];
  included?: Resource[];
  links?: { first: string | null; last: string | null; prev: string | null; next: string | null };
  meta?: PaginationMeta;
}

export interface MetaDocument<M> {
  meta: M;
}

/** A flattened resource: `{ id, ...attributes }`. */
export type Flat<A> = { id: string } & A;

export interface Paginated<T> {
  items: T[];
  meta: PaginationMeta;
}

/** Query string params understood by the API (filters/sort/pagination). */
export type QueryParams = Record<string, string | number | boolean | undefined | null>;
