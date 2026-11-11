import { useState, type ChangeEvent, type FormEvent } from 'react';
import toast from 'react-hot-toast';
import { useUpdateProfile } from '@/api/account';
import { Button, Card, CardBody, CardHeader, Field, Input } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import { useAuthStore } from '@/stores/auth';

export default function ProfilePage() {
  const user = useAuthStore((s) => s.user);
  const update = useUpdateProfile();
  const { errorFor, handleError, clearError } = useFormErrors();
  const [form, setForm] = useState({ name: user?.name ?? '', email: user?.email ?? '', phone: user?.phone ?? '' });

  const dirty = form.name !== user?.name || form.email !== user?.email || form.phone !== (user?.phone ?? '');

  const submit = (e: FormEvent) => {
    e.preventDefault();
    update.mutate(
      { name: form.name.trim(), email: form.email.trim(), phone: form.phone.trim() || undefined },
      { onSuccess: () => toast.success('Profile updated'), onError: handleError },
    );
  };

  const field = (key: keyof typeof form) => ({
    value: form[key],
    onChange: (e: ChangeEvent<HTMLInputElement>) => {
      setForm((f) => ({ ...f, [key]: e.target.value }));
      clearError(key);
    },
  });

  return (
    <Card>
      <CardHeader title="Profile" description="Used to pre-fill your delivery details at checkout." />
      <form onSubmit={submit} noValidate>
        <CardBody className="grid gap-5 sm:grid-cols-2">
          <Field label="Full name" error={errorFor('name')} className="sm:col-span-2">
            <Input autoComplete="name" {...field('name')} />
          </Field>
          <Field label="Email" error={errorFor('email')}>
            <Input type="email" autoComplete="email" {...field('email')} />
          </Field>
          <Field label="Phone" error={errorFor('phone')} optional>
            <Input type="tel" autoComplete="tel" {...field('phone')} />
          </Field>
        </CardBody>
        <div className="flex justify-end gap-2 border-t border-line bg-canvas px-5 py-3.5 sm:px-6">
          <Button type="submit" loading={update.isPending} disabled={!dirty}>
            Save changes
          </Button>
        </div>
      </form>
    </Card>
  );
}
