import axios from 'axios';

export interface ApiErrorObject {
  status?: string;
  title?: string;
  detail?: string;
  source?: { pointer?: string };
  meta?: { field?: string };
}

export interface ApiErrorBody {
  message?: string;
  errors?: ApiErrorObject[];
}

/** Field path (e.g. `items.0.quantity`) → first error message. */
export type FieldErrors = Record<string, string>;

/** Normalised error thrown by the API layer. */
export class ApiError extends Error {
  readonly status: number;
  readonly fieldErrors: FieldErrors;

  constructor(message: string, status: number, errors: ApiErrorObject[] = []) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.fieldErrors = {};
    for (const e of errors) {
      const field = e.meta?.field ?? pointerToField(e.source?.pointer);
      if (field && e.detail && !this.fieldErrors[field]) this.fieldErrors[field] = e.detail;
    }
  }

  get isValidation(): boolean {
    return this.status === 422;
  }
}

function pointerToField(pointer?: string): string | undefined {
  if (!pointer) return undefined;
  return pointer.replace(/^\//, '').split('/').join('.') || undefined;
}

const FALLBACK: Record<number, string> = {
  0: 'Cannot reach the server. Is the API running?',
  401: 'Please sign in to continue.',
  403: 'You are not allowed to do that.',
  404: 'We could not find what you were looking for.',
  429: 'Too many requests — please wait a moment.',
};

export function toApiError(error: unknown): ApiError {
  if (error instanceof ApiError) return error;
  if (axios.isAxiosError<ApiErrorBody>(error)) {
    const status = error.response?.status ?? 0;
    const body = error.response?.data;
    const errors = Array.isArray(body?.errors) ? body.errors : [];
    const fallback = FALLBACK[status] ?? 'Something went wrong. Please try again.';
    const message =
      status === 422
        ? (errors[0]?.detail ?? body?.message ?? fallback)
        : (body?.message ?? errors[0]?.detail ?? fallback);
    return new ApiError(message, status, errors);
  }
  if (error instanceof Error) return new ApiError(error.message, 0);
  return new ApiError('Unexpected error', 0);
}

export function errorMessage(error: unknown): string {
  return toApiError(error).message;
}
