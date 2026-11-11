import { useState } from 'react';
import { Wand2 } from 'lucide-react';
import { Button, Input, Switch } from '@/components/ui';
import { cn } from '@/lib/cn';
import type { VariantPricing } from '@/api/owner';

export interface VariantRow {
  /** Product variant id (what the API expects as `variants[].variant_id`). */
  variant_id: number;
  sku: string;
  label: string | null;
  price: string;
  compare_at_price: string;
  stock: string;
  is_active: boolean;
}

export const toVariantPayload = (rows: VariantRow[]): VariantPricing[] =>
  rows.map((r) => ({
    variant_id: r.variant_id,
    price: r.price,
    compare_at_price: r.compare_at_price || null,
    stock: Number.parseInt(r.stock || '0', 10),
    is_active: r.is_active,
  }));

/** Client-side checks mirroring the API rules; keys match the API's error fields. */
export function validateVariantRows(rows: VariantRow[]): Record<string, string> {
  const errors: Record<string, string> = {};
  rows.forEach((r, i) => {
    const price = Number(r.price);
    if (r.price === '' || !Number.isFinite(price) || price < 0) errors[`variants.${i}.price`] = 'Enter a valid price.';
    if (r.compare_at_price !== '' && Number(r.compare_at_price) <= price)
      errors[`variants.${i}.compare_at_price`] = 'Must be higher than the price.';
    if (r.stock === '' || !/^\d+$/.test(r.stock)) errors[`variants.${i}.stock`] = 'Whole number ≥ 0.';
  });
  return errors;
}

interface VariantPricingTableProps {
  rows: VariantRow[];
  onChange: (rows: VariantRow[]) => void;
  errors: Record<string, string>;
  onClearError?: (field: string) => void;
}

const cellInput = 'h-9 text-[0.8125rem]';

export function VariantPricingTable({ rows, onChange, errors, onClearError }: VariantPricingTableProps) {
  const [bulkPrice, setBulkPrice] = useState('');
  const [bulkStock, setBulkStock] = useState('');

  const update = (index: number, patch: Partial<VariantRow>) => {
    onChange(rows.map((r, i) => (i === index ? { ...r, ...patch } : r)));
    Object.keys(patch).forEach((k) => onClearError?.(`variants.${index}.${k}`));
  };

  const applyAll = () => {
    onChange(
      rows.map((r) => ({
        ...r,
        price: bulkPrice !== '' ? bulkPrice : r.price,
        stock: bulkStock !== '' ? bulkStock : r.stock,
      })),
    );
    rows.forEach((_, i) => {
      if (bulkPrice !== '') onClearError?.(`variants.${i}.price`);
      if (bulkStock !== '') onClearError?.(`variants.${i}.stock`);
    });
  };

  return (
    <div className="overflow-hidden rounded-xl border border-line">
      {rows.length > 1 && (
        <div className="flex flex-wrap items-end gap-2 border-b border-line bg-canvas px-3 py-3">
          <div className="w-28">
            <label htmlFor="bulk-price" className="mb-1 block text-xs font-medium text-muted">
              Price for all
            </label>
            <Input id="bulk-price" type="number" min={0} step="0.01" inputMode="decimal" className={cellInput} value={bulkPrice} onChange={(e) => setBulkPrice(e.target.value)} placeholder="৳" />
          </div>
          <div className="w-24">
            <label htmlFor="bulk-stock" className="mb-1 block text-xs font-medium text-muted">
              Stock for all
            </label>
            <Input id="bulk-stock" type="number" min={0} step="1" inputMode="numeric" className={cellInput} value={bulkStock} onChange={(e) => setBulkStock(e.target.value)} />
          </div>
          <Button size="sm" variant="outline" leadingIcon={<Wand2 className="size-3.5" />} onClick={applyAll} disabled={bulkPrice === '' && bulkStock === ''}>
            Apply to all
          </Button>
        </div>
      )}
      <div className="overflow-x-auto">
        <table className="w-full min-w-[38rem] text-sm">
          <thead className="bg-canvas">
            <tr className="text-left text-xs font-medium tracking-wide text-muted uppercase">
              <th scope="col" className="px-3 py-2">Variant</th>
              <th scope="col" className="px-2 py-2">Price (৳)</th>
              <th scope="col" className="px-2 py-2">Compare at</th>
              <th scope="col" className="px-2 py-2">Stock</th>
              <th scope="col" className="px-3 py-2 text-right">Active</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-line">
            {rows.map((row, i) => {
              const err = (f: string) => errors[`variants.${i}.${f}`];
              const name = row.label ?? 'Default';
              return (
                <tr key={row.variant_id} className={cn(!row.is_active && 'bg-canvas/70')}>
                  <td className="px-3 py-2 align-top">
                    <p className={cn('font-medium', !row.is_active && 'text-muted')}>{name}</p>
                    <p className="font-mono text-xs text-muted">{row.sku}</p>
                    {errors[`variants.${i}.variant_id`] && <p className="text-xs text-danger-600">{errors[`variants.${i}.variant_id`]}</p>}
                  </td>
                  <td className="w-32 px-2 py-2 align-top">
                    <Input
                      type="number" min={0} step="0.01" inputMode="decimal"
                      className={cellInput}
                      aria-label={`Price for ${name}`}
                      aria-invalid={!!err('price') || undefined}
                      value={row.price}
                      onChange={(e) => update(i, { price: e.target.value })}
                    />
                    {err('price') && <p className="mt-1 text-xs text-danger-600">{err('price')}</p>}
                  </td>
                  <td className="w-32 px-2 py-2 align-top">
                    <Input
                      type="number" min={0} step="0.01" inputMode="decimal"
                      className={cellInput}
                      aria-label={`Compare-at price for ${name}`}
                      aria-invalid={!!err('compare_at_price') || undefined}
                      value={row.compare_at_price}
                      placeholder="—"
                      onChange={(e) => update(i, { compare_at_price: e.target.value })}
                    />
                    {err('compare_at_price') && <p className="mt-1 text-xs text-danger-600">{err('compare_at_price')}</p>}
                  </td>
                  <td className="w-24 px-2 py-2 align-top">
                    <Input
                      type="number" min={0} step="1" inputMode="numeric"
                      className={cellInput}
                      aria-label={`Stock for ${name}`}
                      aria-invalid={!!err('stock') || undefined}
                      value={row.stock}
                      onChange={(e) => update(i, { stock: e.target.value })}
                    />
                    {err('stock') && <p className="mt-1 text-xs text-danger-600">{err('stock')}</p>}
                  </td>
                  <td className="px-3 py-2 text-right align-top">
                    <div className="pt-1.5">
                      <Switch size="sm" checked={row.is_active} onChange={(v) => update(i, { is_active: v })} label={`${name} active`} />
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
