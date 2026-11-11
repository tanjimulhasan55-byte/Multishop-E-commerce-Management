import { twMerge } from 'tailwind-merge';

type ClassValue = string | number | bigint | boolean | null | undefined;

/**
 * Join conditional class names, letting later Tailwind classes override
 * earlier conflicting ones (e.g. a caller's `hidden sm:inline-flex` beats a
 * component's default `inline-flex`).
 */
export function cn(...classes: ClassValue[]): string {
  return twMerge(classes.filter((c) => typeof c === 'string' && c.length > 0).join(' '));
}
