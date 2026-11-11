import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/cn';

interface EmptyStateProps {
  icon: LucideIcon;
  title: string;
  description?: ReactNode;
  action?: ReactNode;
  className?: string;
  compact?: boolean;
}

export function EmptyState({ icon: Icon, title, description, action, className, compact }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center text-center', compact ? 'px-4 py-10' : 'px-6 py-16 sm:py-20', className)}>
      <div className="relative mb-5">
        <div className="absolute inset-0 -m-3 rounded-full bg-brand-100/60 blur-xl" aria-hidden />
        <div className="relative flex size-14 items-center justify-center rounded-2xl border border-line bg-surface shadow-card">
          <Icon className="size-6 text-brand-600" strokeWidth={1.75} aria-hidden />
        </div>
      </div>
      <h3 className="text-base font-semibold">{title}</h3>
      {description && <p className="mt-1.5 max-w-sm text-sm text-muted">{description}</p>}
      {action && <div className="mt-6 flex flex-wrap justify-center gap-2">{action}</div>}
    </div>
  );
}
