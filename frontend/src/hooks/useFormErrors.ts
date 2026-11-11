import { useCallback, useState } from 'react';
import toast from 'react-hot-toast';
import { toApiError, type FieldErrors } from '@/api/errors';

/**
 * Field-level errors for a form. `handleError` maps a 422's `meta.field`
 * entries onto fields and toasts anything else.
 */
export function useFormErrors() {
  const [errors, setErrors] = useState<FieldErrors>({});

  const handleError = useCallback((error: unknown) => {
    const apiError = toApiError(error);
    if (apiError.isValidation) {
      setErrors(apiError.fieldErrors);
      toast.error('Please fix the highlighted fields.');
    } else {
      toast.error(apiError.message);
    }
  }, []);

  const clearError = useCallback((field: string) => {
    setErrors((prev) => {
      if (!(field in prev)) return prev;
      const next = { ...prev };
      delete next[field];
      return next;
    });
  }, []);

  const reset = useCallback(() => setErrors({}), []);

  /** First error whose key equals `field` or starts with `field.` (e.g. `name` → `name.en`). */
  const errorFor = useCallback(
    (field: string): string | undefined =>
      errors[field] ?? Object.entries(errors).find(([k]) => k.startsWith(`${field}.`))?.[1],
    [errors],
  );

  return { errors, errorFor, handleError, clearError, reset, setErrors };
}
