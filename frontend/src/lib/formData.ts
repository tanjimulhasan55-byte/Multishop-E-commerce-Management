/** A value that can be serialised into multipart bracket notation. */
export type FormValue =
  | string
  | number
  | boolean
  | File
  | null
  | undefined
  | FormValue[]
  | { [key: string]: FormValue };

/**
 * Serialise nested data with Laravel-style bracket keys:
 * `{ name: { en: 'x' } }` → `name[en]=x`, `variants[0][variant_id]=3`,
 * booleans → `1`/`0`. `undefined` is skipped; `null` becomes an empty string.
 */
export function toFormData(data: Record<string, FormValue>, form = new FormData(), prefix = ''): FormData {
  for (const [key, value] of Object.entries(data)) {
    const name = prefix ? `${prefix}[${key}]` : key;
    append(form, name, value);
  }
  return form;
}

function append(form: FormData, name: string, value: FormValue): void {
  if (value === undefined) return;
  if (value === null) form.append(name, '');
  else if (value instanceof File) form.append(name, value);
  else if (typeof value === 'boolean') form.append(name, value ? '1' : '0');
  else if (typeof value === 'number' || typeof value === 'string') form.append(name, String(value));
  else if (Array.isArray(value)) value.forEach((v, i) => append(form, `${name}[${i}]`, v));
  else toFormData(value, form, name);
}
