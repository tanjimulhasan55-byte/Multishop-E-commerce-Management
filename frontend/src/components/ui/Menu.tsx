import { useEffect, useId, useRef, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { cn } from '@/lib/cn';

interface MenuProps {
  /** Renders the trigger; spread the given props onto a button. */
  trigger: (props: {
    'aria-haspopup': 'menu';
    'aria-expanded': boolean;
    'aria-controls': string;
    onClick: () => void;
  }) => ReactNode;
  children: ReactNode;
  align?: 'left' | 'right';
  className?: string;
}

/** Lightweight dropdown menu: click-outside + Esc + arrow-key navigation. */
export function Menu({ trigger, children, align = 'right', className }: MenuProps) {
  const [open, setOpen] = useState(false);
  const root = useRef<HTMLDivElement>(null);
  const list = useRef<HTMLDivElement>(null);
  const id = useId();

  useEffect(() => {
    if (!open) return;
    const items = () => Array.from(list.current?.querySelectorAll<HTMLElement>('[role="menuitem"]') ?? []);
    items()[0]?.focus();

    const onPointer = (e: PointerEvent) => {
      if (!root.current?.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setOpen(false);
        root.current?.querySelector<HTMLElement>('[aria-haspopup]')?.focus();
      }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const all = items();
        const index = all.indexOf(document.activeElement as HTMLElement);
        const next = e.key === 'ArrowDown' ? (index + 1) % all.length : (index - 1 + all.length) % all.length;
        all[next]?.focus();
      }
    };
    document.addEventListener('pointerdown', onPointer);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('pointerdown', onPointer);
      document.removeEventListener('keydown', onKey);
    };
  }, [open]);

  return (
    <div ref={root} className="relative">
      {trigger({ 'aria-haspopup': 'menu', 'aria-expanded': open, 'aria-controls': id, onClick: () => setOpen((o) => !o) })}
      {open && (
        <div
          ref={list}
          id={id}
          role="menu"
          onClick={() => setOpen(false)}
          className={cn(
            'absolute top-full z-40 mt-2 min-w-56 origin-top animate-scale-in rounded-xl border border-line bg-surface p-1.5 shadow-pop',
            align === 'right' ? 'right-0' : 'left-0',
            className,
          )}
        >
          {children}
        </div>
      )}
    </div>
  );
}

const itemClass =
  'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-ink-soft transition-colors duration-150 hover:bg-subtle hover:text-ink focus:bg-subtle focus:text-ink focus:outline-none';

export function MenuLink({ to, icon, children, external }: { to: string; icon?: ReactNode; children: ReactNode; external?: boolean }) {
  if (external) {
    return (
      <a role="menuitem" href={to} className={itemClass} target="_blank" rel="noreferrer">
        {icon}
        {children}
      </a>
    );
  }
  return (
    <Link role="menuitem" to={to} className={itemClass}>
      {icon}
      {children}
    </Link>
  );
}

export function MenuButton({ onClick, icon, children, danger }: { onClick: () => void; icon?: ReactNode; children: ReactNode; danger?: boolean }) {
  return (
    <button role="menuitem" type="button" onClick={onClick} className={cn(itemClass, danger && 'text-danger-600 hover:text-danger-700')}>
      {icon}
      {children}
    </button>
  );
}

export function MenuSeparator() {
  return <div role="separator" className="my-1.5 h-px bg-line" />;
}
