import type { Money } from '@/types/models';

const moneyFormatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export function toNumber(value: Money | number | null | undefined): number {
  if (value === null || value === undefined || value === '') return 0;
  const n = typeof value === 'number' ? value : Number.parseFloat(value);
  return Number.isFinite(n) ? n : 0;
}

/** `৳ 1,250.00` */
export function formatMoney(value: Money | number | null | undefined): string {
  return `৳ ${moneyFormatter.format(toNumber(value))}`;
}

export function discountPercent(price: Money, compareAt: Money | null): number | null {
  const p = toNumber(price);
  const c = toNumber(compareAt);
  if (!compareAt || c <= p || c <= 0) return null;
  return Math.round(((c - p) / c) * 100);
}

const dateFormatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const dateTimeFormatter = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

export const formatDate = (iso: string): string => dateFormatter.format(new Date(iso));
export const formatDateTime = (iso: string): string => dateTimeFormatter.format(new Date(iso));

export const formatNumber = (n: number): string => new Intl.NumberFormat('en-US').format(n);

export const pluralize = (n: number, one: string, many = `${one}s`): string => `${formatNumber(n)} ${n === 1 ? one : many}`;
