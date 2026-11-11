import { useState, type ChangeEvent, type FormEvent } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useRegister, type RegisterPayload } from '@/api/auth';
import { Button, Field, Input } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import { AuthShell } from './AuthShell';
import { safeRedirect } from './guards';

export default function RegisterPage() {
  const [params] = useSearchParams();
  const redirect = params.get('redirect');
  const navigate = useNavigate();
  const register = useRegister();
  const { errorFor, handleError, clearError, setErrors } = useFormErrors();
  const [form, setForm] = useState<Required<RegisterPayload>>({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });

  const set = (key: keyof RegisterPayload) => (e: ChangeEvent<HTMLInputElement>) => {
    setForm((f) => ({ ...f, [key]: e.target.value }));
    clearError(key);
  };

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: 'Passwords do not match.' });
      return;
    }
    try {
      const user = await register.mutateAsync({ ...form, phone: form.phone.trim() || undefined });
      toast.success(`Welcome to MultiShop, ${user.name.split(' ')[0]}!`);
      navigate(safeRedirect(redirect), { replace: true });
    } catch (err) {
      handleError(err);
    }
  };

  return (
    <AuthShell
      title="Create your account"
      subtitle="Track orders and check out faster."
      footer={
        <>
          Already have an account?{' '}
          <Link to={`/login${redirect ? `?redirect=${encodeURIComponent(redirect)}` : ''}`} className="font-medium text-brand-600 hover:text-brand-700">
            Sign in
          </Link>
        </>
      }
    >
      <form onSubmit={submit} noValidate className="space-y-5">
        <Field label="Full name" error={errorFor('name')} required>
          <Input autoComplete="name" value={form.name} onChange={set('name')} data-autofocus />
        </Field>
        <Field label="Email" error={errorFor('email')} required>
          <Input type="email" autoComplete="email" value={form.email} onChange={set('email')} />
        </Field>
        <Field label="Phone" error={errorFor('phone')} optional>
          <Input type="tel" autoComplete="tel" value={form.phone} onChange={set('phone')} placeholder="01XXXXXXXXX" />
        </Field>
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Password" error={errorFor('password')} required hint="At least 8 characters.">
            <Input type="password" autoComplete="new-password" value={form.password} onChange={set('password')} />
          </Field>
          <Field label="Confirm" error={errorFor('password_confirmation')} required>
            <Input type="password" autoComplete="new-password" value={form.password_confirmation} onChange={set('password_confirmation')} />
          </Field>
        </div>
        <Button type="submit" block size="lg" loading={register.isPending}>
          Create account
        </Button>
      </form>
    </AuthShell>
  );
}
