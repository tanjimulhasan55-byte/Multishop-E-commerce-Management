import type { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';
import { Skeleton } from './Skeleton';

/** Scrollable table container with a sticky header. */
export function TableShell({ children, className, maxHeight = 'max-h-[70vh]' }: { children: ReactNode; className?: string; maxHeight?: string }) {
  return (
    <div className={cn('overflow-auto', maxHeight, className)}>
      <table className="w-full min-w-[40rem] border-separate border-spacing-0 text-sm">{children}</table>
    </div>
  );
}

export function THead({ children }: { children: ReactNode }) {
  return <thead className="sticky top-0 z-10 bg-canvas/95 backdrop-blur">{children}</thead>;
}

export function Th({ className, ...rest }: ThHTMLAttributes<HTMLTableCellElement>) {
  return (
    <th
      scope="col"
      className={cn(
        'border-b border-line px-4 py-2.5 text-left text-xs font-medium tracking-wide whitespace-nowrap text-muted uppercase first:pl-5 last:pr-5 sm:first:pl-6 sm:last:pr-6',
        className,
      )}
      {...rest}
    />
  );
}

export function Tr({ className, ...rest }: HTMLAttributes<HTMLTableRowElement>) {
  return <tr className={cn('group transition-colors duration-150 hover:bg-canvas', className)} {...rest} />;
}

export function Td({ className, ...rest }: TdHTMLAttributes<HTMLTableCellElement>) {
  return (
    <td
      className={cn(
        'border-b border-line px-4 py-3 align-middle first:pl-5 last:pr-5 group-last:border-b-0 sm:first:pl-6 sm:last:pr-6',
        className,
      )}
      {...rest}
    />
  );
}

export function TableSkeletonRows({ rows = 6, cols }: { rows?: number; cols: number }) {
  return (
    <>
      {Array.from({ length: rows }, (_, r) => (
        <tr key={r} aria-hidden>
          {Array.from({ length: cols }, (_, c) => (
            <Td key={c}>
              <Skeleton className={cn('h-4', c === 0 ? 'w-40' : 'w-16')} />
            </Td>
          ))}
        </tr>
      ))}
    </>
  );
}
