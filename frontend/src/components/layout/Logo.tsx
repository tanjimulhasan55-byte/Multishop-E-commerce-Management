import { Link } from 'react-router-dom';
import { cn } from '@/lib/cn';

export function Logo({ to = '/', className, suffix }: { to?: string; className?: string; suffix?: string }) {
  return (
    <Link to={to} className={cn('group inline-flex items-center gap-2.5 rounded-lg', className)} aria-label="MultiShop home">
      <span className="relative flex size-8 items-center justify-center rounded-[0.6rem] bg-gradient-to-br from-brand-500 to-accent-500 shadow-[0_6px_16px_-6px] shadow-brand-500/60 transition-transform duration-200 group-hover:-rotate-6">
        <svg viewBox="0 0 24 24" className="size-[18px] text-white" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinejoin="round" aria-hidden>
          <path d="M5 9h14l-1.2 10.5H6.2z" />
          <path d="M9 9a3 3 0 0 1 6 0" strokeLinecap="round" />
        </svg>
      </span>
      <span className="text-[1.0625rem] font-bold tracking-tight text-ink">
        MultiShop
        {suffix && <span className="ml-1.5 font-medium text-muted">{suffix}</span>}
      </span>
    </Link>
  );
}
