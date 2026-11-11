import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/cn';
import { Skeleton } from './Skeleton';

interface StatCardProps {
  label: string;
  value: ReactNode;
  icon: LucideIcon;
  hint?: ReactNode;
  tone?: 'brand' | 'success' | 'warning' | 'info';
  loading?: boolean;
}

const tones = {
  brand: 'bg-brand-50 text-brand-600',
  success: 'bg-success-50 text-success-600',
  warning: 'bg-warning-50 text-warning-600',
  info: 'bg-info-50 text-info-600',
};

export function StatCard({ label, value, icon: Icon, hint, tone = 'brand', loading }: StatCardProps) {
  return (
    <div className="rounded-2xl border border-line bg-surface p-5 shadow-xs">
      <div className="flex items-center justify-between">
        <p className="text-[0.8125rem] font-medium text-muted">{label}</p>
        <span className={cn('flex size-8 items-center justify-center rounded-lg', tones[tone])}>
          <Icon className="size-4" aria-hidden />
        </span>
      </div>
      <div className="mt-3 text-2xl font-semibold tracking-tight tabular-nums">{loading ? <Skeleton className="h-8 w-24" /> : value}</div>
      {hint && <div className="mt-1 text-[0.8125rem] text-muted">{hint}</div>}
    </div>
  );
}
