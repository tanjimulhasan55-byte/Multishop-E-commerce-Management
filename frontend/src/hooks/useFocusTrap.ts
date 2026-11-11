import { useEffect, useRef, type RefObject } from 'react';

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/** Active traps, innermost last. */
const stack: symbol[] = [];

/**
 * Keeps keyboard focus inside `ref` while `active`, closes on Escape,
 * locks body scroll and restores focus to the previously focused element.
 */
export function useFocusTrap<T extends HTMLElement>(active: boolean, onEscape: () => void): RefObject<T | null> {
  const ref = useRef<T>(null);
  const onEscapeRef = useRef(onEscape);
  onEscapeRef.current = onEscape;

  useEffect(() => {
    if (!active) return;
    const node = ref.current;
    const previouslyFocused = document.activeElement as HTMLElement | null;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const focusables = () =>
      Array.from(node?.querySelectorAll<HTMLElement>(FOCUSABLE) ?? []).filter((el) => el.offsetParent !== null);

    // Prefer an explicitly marked element, then the first field, then the panel.
    const initial = node?.querySelector<HTMLElement>('[data-autofocus]') ?? focusables()[0] ?? node;
    initial?.focus({ preventScroll: true });

    const token = Symbol('trap');
    stack.push(token);

    const onKeyDown = (e: KeyboardEvent) => {
      // Only the top-most overlay (e.g. a confirm over a drawer) reacts.
      if (stack[stack.length - 1] !== token) return;
      if (e.key === 'Escape') {
        e.stopPropagation();
        onEscapeRef.current();
        return;
      }
      if (e.key !== 'Tab' || !node) return;
      const items = focusables();
      if (items.length === 0) {
        e.preventDefault();
        return;
      }
      const first = items[0];
      const last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };

    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('keydown', onKeyDown);
      stack.splice(stack.indexOf(token), 1);
      document.body.style.overflow = previousOverflow;
      previouslyFocused?.focus?.({ preventScroll: true });
    };
  }, [active]);

  return ref;
}
