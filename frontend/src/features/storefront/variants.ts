import type { ListingVariant, ProductOption } from '@/types/models';

/** attribute_id → selected option value id */
export type Selection = Record<number, number>;

export const isBuyable = (v: ListingVariant): boolean => v.is_active && v.in_stock && v.stock > 0;

function matches(variant: ListingVariant, selection: Selection, ignoreAttribute?: number): boolean {
  return Object.entries(selection).every(
    ([attr, valueId]) => Number(attr) === ignoreAttribute || variant.option_value_ids.includes(valueId),
  );
}

/** The variant matching a complete selection (or the only variant for simple products). */
export function resolveVariant(options: ProductOption[], variants: ListingVariant[], selection: Selection): ListingVariant | undefined {
  const active = variants.filter((v) => v.is_active);
  if (options.length === 0) return active[0] ?? variants[0];
  if (options.some((o) => selection[o.attribute_id] === undefined)) return undefined;
  return active.find((v) => matches(v, selection));
}

/**
 * Can `valueId` of `option` be picked given the other current choices?
 * True when some active, in-stock variant has it together with the rest.
 */
export function isValueAvailable(
  option: ProductOption,
  valueId: number,
  variants: ListingVariant[],
  selection: Selection,
): boolean {
  return variants.some(
    (v) => isBuyable(v) && v.option_value_ids.includes(valueId) && matches(v, selection, option.attribute_id),
  );
}

/** Pre-select the first buyable variant (falls back to the first active one). */
export function initialSelection(options: ProductOption[], variants: ListingVariant[]): Selection {
  const pick = variants.find(isBuyable) ?? variants.find((v) => v.is_active);
  if (!pick) return {};
  const selection: Selection = {};
  for (const option of options) {
    const value = option.values.find((val) => pick.option_value_ids.includes(val.id));
    if (value) selection[option.attribute_id] = value.id;
  }
  return selection;
}

/**
 * Choose a value; if the other choices no longer form a buyable variant,
 * move them to the nearest buyable combination that keeps the new value.
 */
export function selectValue(
  options: ProductOption[],
  variants: ListingVariant[],
  selection: Selection,
  attributeId: number,
  valueId: number,
): Selection {
  const next = { ...selection, [attributeId]: valueId };
  if (variants.some((v) => isBuyable(v) && matches(v, next))) return next;
  const fallback = variants.find((v) => isBuyable(v) && v.option_value_ids.includes(valueId));
  if (!fallback) return next;
  const adjusted: Selection = {};
  for (const option of options) {
    const value = option.values.find((val) => fallback.option_value_ids.includes(val.id));
    if (value) adjusted[option.attribute_id] = value.id;
  }
  return adjusted;
}
