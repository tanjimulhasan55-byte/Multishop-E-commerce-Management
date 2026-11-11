import {
  cloneElement,
  forwardRef,
  isValidElement,
  useId,
  type InputHTMLAttributes,
  type ReactElement,
  type ReactNode,
  type SelectHTMLAttributes,
  type TextareaHTMLAttributes,
} from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/cn';

/* ---------- Shared control styling ---------- */

export function controlClasses(invalid?: boolean): string {
  return cn(
    'block w-full rounded-lg border bg-surface text-sm text-ink shadow-xs placeholder:text-faint',
    'transition-[border-color,box-shadow] duration-150',
    'focus:outline-none focus:shadow-[var(--shadow-focus)]',
    'disabled:cursor-not-allowed disabled:bg-subtle disabled:text-muted',
    invalid ? 'border-danger-500 focus:border-danger-500' : 'border-line-strong hover:border-faint focus:border-brand-500',
  );
}

/* ---------- Field: label + control + hint/error ---------- */

interface ControlA11yProps {
  id?: string;
  'aria-invalid'?: boolean;
  'aria-describedby'?: string;
}

export interface FieldProps {
  label?: ReactNode;
  hint?: ReactNode;
  error?: string;
  required?: boolean;
  optional?: boolean;
  className?: string;
  /** A single control element; it receives id / aria wiring. */
  children: ReactElement<ControlA11yProps>;
  /** Visually hide the label but keep it for screen readers. */
  srOnlyLabel?: boolean;
}

export function Field({ label, hint, error, required, optional, className, children, srOnlyLabel }: FieldProps) {
  const autoId = useId();
  const id = children.props.id ?? autoId;
  const hintId = hint ? `${id}-hint` : undefined;
  const errorId = error ? `${id}-error` : undefined;
  const describedBy = [errorId, hintId].filter(Boolean).join(' ') || undefined;

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      {label && (
        <label htmlFor={id} className={cn('text-[0.8125rem] font-medium text-ink-soft', srOnlyLabel && 'sr-only')}>
          {label}
          {required && <span className="ml-0.5 text-danger-600" aria-hidden>*</span>}
          {optional && <span className="ml-1.5 font-normal text-faint">Optional</span>}
        </label>
      )}
      {isValidElement(children)
        ? cloneElement(children, { id, 'aria-invalid': !!error || undefined, 'aria-describedby': describedBy })
        : children}
      {error ? (
        <p id={errorId} className="text-[0.8125rem] text-danger-600" role="alert">
          {error}
        </p>
      ) : hint ? (
        <p id={hintId} className="text-[0.8125rem] text-muted">
          {hint}
        </p>
      ) : null}
    </div>
  );
}

/* ---------- Controls ---------- */

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  leading?: ReactNode;
  trailing?: ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input({ className, leading, trailing, ...rest }, ref) {
  const invalid = rest['aria-invalid'] === true;
  if (!leading && !trailing) {
    return <input ref={ref} className={cn(controlClasses(invalid), 'h-10 px-3', className)} {...rest} />;
  }
  return (
    <div className="relative">
      {leading && (
        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">{leading}</span>
      )}
      <input
        ref={ref}
        className={cn(controlClasses(invalid), 'h-10', leading ? 'pl-9' : 'pl-3', trailing ? 'pr-10' : 'pr-3', className)}
        {...rest}
      />
      {trailing && <span className="absolute inset-y-0 right-0 flex items-center pr-2 text-muted">{trailing}</span>}
    </div>
  );
});

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(function Textarea(
  { className, rows = 4, ...rest },
  ref,
) {
  return (
    <textarea
      ref={ref}
      rows={rows}
      className={cn(controlClasses(rest['aria-invalid'] === true), 'px-3 py-2.5 leading-relaxed', className)}
      {...rest}
    />
  );
});

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(function Select(
  { className, children, ...rest },
  ref,
) {
  return (
    <div className="relative">
      <select
        ref={ref}
        className={cn(controlClasses(rest['aria-invalid'] === true), 'h-10 appearance-none pr-9 pl-3', className)}
        {...rest}
      >
        {children}
      </select>
      <ChevronDown className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted" aria-hidden />
    </div>
  );
});

/* ---------- Checkbox ---------- */

interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label: ReactNode;
  description?: ReactNode;
}

export function Checkbox({ label, description, className, ...rest }: CheckboxProps) {
  return (
    <label className={cn('group flex cursor-pointer items-start gap-2.5 text-sm', className)}>
      <input
        type="checkbox"
        className="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-line-strong accent-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500"
        {...rest}
      />
      <span className="flex flex-col">
        <span className="text-ink-soft group-hover:text-ink">{label}</span>
        {description && <span className="text-[0.8125rem] text-muted">{description}</span>}
      </span>
    </label>
  );
}
