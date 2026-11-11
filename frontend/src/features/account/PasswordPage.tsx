import { useState, type ChangeEvent, type FormEvent } from 'react';
import toast from 'react-hot-toast';
import { useChangePassword, type PasswordPayload } from '@/api/account';
import { Button, Card, CardBody, CardHeader, Field, Input } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';

const EMPTY: PasswordPayload = { current_password: '', password: '', password_confirmation: '' };

export default function PasswordPage() {
  const change = useChangePassword();
  const { errorFor, handleError, clearError, setErrors } = useFormErrors();
  const [form, setForm] = useState<PasswordPayload>(EMPTY);

  const field = (key: keyof PasswordPayload) => ({
    value: form[key],
    onChange: (e: ChangeEvent<HTMLInputElement>) => {
      setForm((f) => ({ ...f, [key]: e.target.value }));
      clearError(key);
    },
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: 'Passwords do not match.' });
      return;
    }
    change.mutate(form, {
      onSuccess: () => {
        toast.success('Password changed');
        setForm(EMPTY);
      },
      onError: handleError,
    });
  };

  return (
    <Card>
      <CardHeader title="Password" description="Use at least 8 characters. You’ll stay signed in on this device." />
      <form onSubmit={submit} noValidate>
        <CardBody className="grid max-w-md gap-5">
          <Field label="Current password" error={errorFor('current_password')}>
            <Input type="password" autoComplete="current-password" {...field('current_password')} />
          </Field>
          <Field label="New password" error={errorFor('password')}>
            <Input type="password" autoComplete="new-password" {...field('password')} />
          </Field>
          <Field label="Confirm new password" error={errorFor('password_confirmation')}>
            <Input type="password" autoComplete="new-password" {...field('password_confirmation')} />
          </Field>
        </CardBody>
        <div className="flex justify-end border-t border-line bg-canvas px-5 py-3.5 sm:px-6">
          <Button type="submit" loading={change.isPending} disabled={!form.current_password || !form.password}>
            Update password
          </Button>
        </div>
      </form>
    </Card>
  );
}
