import { useState, type FormEvent } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Eye, EyeOff } from 'lucide-react';
import { useLogin } from '@/api/auth';
import { toApiError } from '@/api/errors';
import { Alert, Button, Field, Input } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import { isShopOwner } from '@/stores/auth';
import { AuthShell } from './AuthShell';
import { safeRedirect } from './guards';

const DEMO_ACCOUNTS = [
  { label: 'Customer', email: 'customer@multishop.com' },
  { label: 'Shop owner', email: 'rahim@multishop.com' },
];

export default function LoginPage() {
  const [params] = useSearchParams();
  const redirect = params.get('redirect');
  const navigate = useNavigate();
  const login = useLogin();
  const { errorFor, handleError, clearError, reset } = useFormErrors();
  const [form, setForm] = useState({ email: '', password: '' });
  const [showPassword, setShowPassword] = useState(false);
  const [failure, setFailure] = useState<string | null>(null);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    reset();
    setFailure(null);
    try {
      const user = await login.mutateAsync(form);
      toast.success(`Welcome back, ${user.name.split(' ')[0]}`);
      navigate(safeRedirect(redirect, isShopOwner(user) ? '/owner' : '/'), { replace: true });
    } catch (err) {
      // The API answers bad credentials with 422 on `email` (or 401).
      const apiError = toApiError(err);
      if (apiError.status === 401 || (apiError.isValidation && /credentials/i.test(apiError.message))) setFailure(apiError.message);
      else handleError(err);
    }
  };

  return (
    <AuthShell
      title="Sign in"
      subtitle={redirect?.startsWith('/checkout') ? 'Sign in to complete your order.' : 'Welcome back — sign in to your account.'}
      footer={
        <>
          New to MultiShop?{' '}
          <Link to={`/register${redirect ? `?redirect=${encodeURIComponent(redirect)}` : ''}`} className="font-medium text-brand-600 hover:text-brand-700">
            Create an account
          </Link>
        </>
      }
    >
      <form onSubmit={submit} noValidate className="space-y-5">
        {failure && <Alert tone="danger">{failure}</Alert>}
        <Field label="Email" error={errorFor('email')}>
          <Input
            type="email"
            autoComplete="email"
            value={form.email}
            onChange={(e) => {
              setForm({ ...form, email: e.target.value });
              clearError('email');
            }}
            required
            data-autofocus
          />
        </Field>
        <Field label="Password" error={errorFor('password')}>
          <Input
            type={showPassword ? 'text' : 'password'}
            autoComplete="current-password"
            value={form.password}
            onChange={(e) => {
              setForm({ ...form, password: e.target.value });
              clearError('password');
            }}
            required
            trailing={
              <button
                type="button"
                onClick={() => setShowPassword((s) => !s)}
                className="rounded-md p-1.5 text-muted hover:text-ink"
                aria-label={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
              </button>
            }
          />
        </Field>
        <Button type="submit" block size="lg" loading={login.isPending}>
          Sign in
        </Button>
      </form>

      <div className="mt-8 rounded-xl border border-dashed border-line-strong bg-canvas p-4">
        <p className="text-xs font-medium tracking-wide text-muted uppercase">Demo accounts · password “password”</p>
        <div className="mt-3 flex flex-wrap gap-2">
          {DEMO_ACCOUNTS.map((a) => (
            <Button key={a.email} variant="outline" size="xs" onClick={() => setForm({ email: a.email, password: 'password' })}>
              {a.label}
            </Button>
          ))}
        </div>
      </div>
    </AuthShell>
  );
}
