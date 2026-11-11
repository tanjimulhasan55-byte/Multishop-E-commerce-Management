import { useId, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';
import { useFocusTrap } from '@/hooks/useFocusTrap';
import { cn } from '@/lib/cn';
import { Button } from './Button';

interface OverlayBaseProps {
  open: boolean;
  onClose: () => void;
  title: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  footer?: ReactNode;
  /** Prevent closing via backdrop / Esc (e.g. while saving). */
  locked?: boolean;
}

interface ModalProps extends OverlayBaseProps {
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

const modalSizes = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };

function Header({ title, description, onClose, titleId, descId }: {
  title: ReactNode;
  description?: ReactNode;
  onClose: () => void;
  titleId: string;
  descId: string;
}) {
  return (
    <div className="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
      <div className="min-w-0">
        <h2 id={titleId} className="text-base font-semibold">
          {title}
        </h2>
        {description && (
          <p id={descId} className="mt-0.5 text-[0.8125rem] text-muted">
            {description}
          </p>
        )}
      </div>
      <Button variant="ghost" size="sm" iconOnly onClick={onClose} aria-label="Close" className="-mr-2 -mt-1">
        <X className="size-4" />
      </Button>
    </div>
  );
}

/** Centered dialog with focus trap, Esc to close and scroll lock. */
export function Modal({ open, onClose, title, description, children, footer, size = 'md', locked }: ModalProps) {
  const close = () => !locked && onClose();
  const ref = useFocusTrap<HTMLDivElement>(open, close);
  const titleId = useId();
  const descId = useId();
  if (!open) return null;

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6">
      <div className="absolute inset-0 animate-fade-in bg-ink/40 backdrop-blur-[2px]" onClick={close} aria-hidden />
      <div
        ref={ref}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={description ? descId : undefined}
        tabIndex={-1}
        className={cn(
          'relative flex max-h-[92dvh] w-full animate-scale-in flex-col overflow-hidden bg-surface shadow-pop outline-none',
          'rounded-t-2xl sm:rounded-2xl',
          modalSizes[size],
        )}
      >
        <Header title={title} description={description} onClose={close} titleId={titleId} descId={descId} />
        <div className="flex-1 overflow-y-auto px-5 py-5 sm:px-6">{children}</div>
        {footer && (
          <div className="flex flex-col-reverse gap-2 border-t border-line bg-canvas px-5 py-3.5 sm:flex-row sm:justify-end sm:px-6">
            {footer}
          </div>
        )}
      </div>
    </div>,
    document.body,
  );
}

interface DrawerProps extends OverlayBaseProps {
  side?: 'right' | 'left';
  width?: 'sm' | 'md' | 'lg' | 'xl';
}

const drawerWidths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-2xl', xl: 'max-w-4xl' };

/** Slide-over panel used for forms and the cart. */
export function Drawer({ open, onClose, title, description, children, footer, side = 'right', width = 'md', locked }: DrawerProps) {
  const close = () => !locked && onClose();
  const ref = useFocusTrap<HTMLDivElement>(open, close);
  const titleId = useId();
  const descId = useId();
  if (!open) return null;

  return createPortal(
    <div className="fixed inset-0 z-50">
      <div className="absolute inset-0 animate-fade-in bg-ink/40 backdrop-blur-[2px]" onClick={close} aria-hidden />
      <div
        ref={ref}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={description ? descId : undefined}
        tabIndex={-1}
        className={cn(
          'absolute inset-y-0 flex w-full flex-col bg-surface shadow-pop outline-none',
          side === 'right' ? 'right-0 animate-slide-in-right' : 'left-0 animate-slide-in-left',
          drawerWidths[width],
        )}
      >
        <Header title={title} description={description} onClose={close} titleId={titleId} descId={descId} />
        <div className="flex-1 overflow-y-auto px-5 py-5 sm:px-6">{children}</div>
        {footer && <div className="flex gap-2 border-t border-line bg-canvas px-5 py-3.5 sm:justify-end sm:px-6">{footer}</div>}
      </div>
    </div>,
    document.body,
  );
}
