import type { ReactNode } from 'react';
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-react';
import { cn } from '@/lib/cn';

type Tone = 'info' | 'success' | 'warning' | 'danger';

const styles: Record<Tone, { box: string; icon: typeof Info }> = {
  info: { box: 'bg-info-50 border-info-600/20 text-info-600', icon: Info },
  success: { box: 'bg-success-50 border-success-600/20 text-success-700', icon: CheckCircle2 },
  warning: { box: 'bg-warning-50 border-warning-600/25 text-warning-700', icon: TriangleAlert },
  danger: { box: 'bg-danger-50 border-danger-600/20 text-danger-700', icon: AlertCircle },
};

interface AlertProps {
  tone?: Tone;
  title?: ReactNode;
  children?: ReactNode;
  onDismiss?: () => void;
  action?: ReactNode;
  className?: string;
}

export function Alert({ tone = 'info', title, children, onDismiss, action, className }: AlertProps) {
  const { box, icon: Icon } = styles[tone];
  return (
    <div role={tone === 'danger' ? 'alert' : 'status'} className={cn('flex gap-3 rounded-xl border px-4 py-3 text-sm', box, className)}>
      <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
      <div className="min-w-0 flex-1">
        {title && <p className="font-medium">{title}</p>}
        {children && <div className={cn('text-ink-soft', title && 'mt-0.5')}>{children}</div>}
        {action && <div className="mt-2">{action}</div>}
      </div>
      {onDismiss && (
        <button type="button" onClick={onDismiss} aria-label="Dismiss" className="-m-1 rounded-md p-1 opacity-70 hover:opacity-100">
          <X className="size-4" />
        </button>
      )}
    </div>
  );
}
