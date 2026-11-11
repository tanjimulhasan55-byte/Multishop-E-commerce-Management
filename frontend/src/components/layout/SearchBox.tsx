import { useEffect, useState, type FormEvent } from 'react';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui';
import { useT } from '@/lib/i18n';

/** Global product search — submits to /products?search=… */
export function SearchBox({ className, onSubmitted }: { className?: string; onSubmitted?: () => void }) {
  const t = useT();
  const navigate = useNavigate();
  const location = useLocation();
  const [params] = useSearchParams();
  const current = location.pathname === '/products' ? (params.get('search') ?? '') : '';
  const [value, setValue] = useState(current);

  useEffect(() => setValue(current), [current]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const q = value.trim();
    navigate(q ? `/products?search=${encodeURIComponent(q)}` : '/products');
    onSubmitted?.();
  };

  return (
    <form role="search" onSubmit={submit} className={className}>
      <label htmlFor="global-search" className="sr-only">
        Search products
      </label>
      <Input
        id="global-search"
        type="search"
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder={t('search')}
        leading={<Search className="size-4" />}
        className="h-10 rounded-xl border-transparent bg-subtle shadow-none hover:border-line-strong focus:bg-surface"
        autoComplete="off"
      />
    </form>
  );
}
