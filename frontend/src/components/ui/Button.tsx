import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { Link, type LinkProps } from 'react-router-dom';
import { cn } from '@/lib/cn';
import { Spinner } from './Spinner';

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'danger-ghost';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg';

const variants: Record<ButtonVariant, string> = {
  primary:
    'bg-brand-600 text-white shadow-xs hover:bg-brand-700 active:bg-brand-800 disabled:bg-brand-300',
  secondary: 'bg-ink text-white shadow-xs hover:bg-ink-soft disabled:bg-faint',
  outline:
    'bg-surface text-ink border border-line-strong shadow-xs hover:bg-subtle hover:border-faint disabled:text-faint',
  ghost: 'text-ink-soft hover:bg-subtle hover:text-ink disabled:text-faint',
  danger: 'bg-danger-600 text-white shadow-xs hover:bg-danger-700 disabled:opacity-60',
  'danger-ghost': 'text-danger-600 hover:bg-danger-50 disabled:opacity-60',
};

const sizes: Record<ButtonSize, string> = {
  xs: 'h-7 px-2.5 text-xs gap-1 rounded-md',
  sm: 'h-8.5 px-3 text-[0.8125rem] gap-1.5 rounded-lg',
  md: 'h-10 px-4 text-sm gap-2 rounded-lg',
  lg: 'h-12 px-6 text-[0.9375rem] gap-2 rounded-xl',
};

const iconOnlySizes: Record<ButtonSize, string> = {
  xs: 'size-7 rounded-md',
  sm: 'size-8.5 rounded-lg',
  md: 'size-10 rounded-lg',
  lg: 'size-12 rounded-xl',
};

interface StyleProps {
  variant?: ButtonVariant;
  size?: ButtonSize;
  iconOnly?: boolean;
  block?: boolean;
}

export function buttonClasses({ variant = 'primary', size = 'md', iconOnly, block }: StyleProps = {}): string {
  return cn(
    'inline-flex shrink-0 select-none items-center justify-center font-medium whitespace-nowrap',
    'transition-[background-color,border-color,color,box-shadow,transform] duration-150 ease-out',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500',
    'disabled:cursor-not-allowed active:translate-y-px disabled:active:translate-y-0',
    variants[variant],
    iconOnly ? iconOnlySizes[size] : sizes[size],
    block && 'w-full',
  );
}

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement>, StyleProps {
  loading?: boolean;
  leadingIcon?: ReactNode;
  trailingIcon?: ReactNode;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant, size, iconOnly, block, loading, leadingIcon, trailingIcon, className, children, disabled, type = 'button', ...rest },
  ref,
) {
  return (
    <button
      ref={ref}
      type={type}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      className={cn(buttonClasses({ variant, size, iconOnly, block }), className)}
      {...rest}
    >
      {loading ? <Spinner className="size-4" /> : leadingIcon}
      {children}
      {!loading && trailingIcon}
    </button>
  );
});

export interface ButtonLinkProps extends LinkProps, StyleProps {
  leadingIcon?: ReactNode;
  trailingIcon?: ReactNode;
}

export function ButtonLink({ variant, size, iconOnly, block, leadingIcon, trailingIcon, className, children, ...rest }: ButtonLinkProps) {
  return (
    <Link className={cn(buttonClasses({ variant, size, iconOnly, block }), className)} {...rest}>
      {leadingIcon}
      {children}
      {trailingIcon}
    </Link>
  );
}
