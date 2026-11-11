import type { ReactNode } from 'react';
import { Logo } from '@/components/layout/Logo';
import { LocaleSwitcher } from '@/components/layout/LocaleSwitcher';

/** Split-screen layout for sign in / sign up. */
export function AuthShell({ title, subtitle, children, footer }: { title: string; subtitle: ReactNode; children: ReactNode; footer: ReactNode }) {
  return (
    <div className="grid min-h-dvh lg:grid-cols-[1fr_minmax(0,36rem)]">
      <aside className="relative hidden overflow-hidden bg-ink lg:block" aria-hidden>
        <div className="absolute -top-32 -left-32 size-[36rem] rounded-full bg-brand-600/40 blur-3xl" />
        <div className="absolute -right-24 -bottom-40 size-[32rem] rounded-full bg-accent-500/30 blur-3xl" />
        <div className="relative flex h-full flex-col justify-between p-12 text-white">
          <div className="text-lg font-bold tracking-tight">MultiShop</div>
          <div>
            <p className="max-w-md text-3xl leading-tight font-semibold tracking-tight">
              “We moved our whole shop online in an afternoon. Orders now come in from all 64 districts.”
            </p>
            <p className="mt-6 text-sm text-white/60">Rahim Uddin — Dhaka Gadget House</p>
          </div>
          <div className="flex gap-8 text-sm text-white/60">
            <span>Cash on delivery</span>
            <span>Bangla & English</span>
            <span>Verified shops</span>
          </div>
        </div>
      </aside>

      <main className="flex flex-col bg-surface px-4 py-6 sm:px-10">
        <div className="flex items-center justify-between">
          <Logo />
          <LocaleSwitcher />
        </div>
        <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
          <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
          <p className="mt-2 text-sm text-muted">{subtitle}</p>
          <div className="mt-8">{children}</div>
          <div className="mt-8 text-center text-sm text-muted">{footer}</div>
        </div>
      </main>
    </div>
  );
}
