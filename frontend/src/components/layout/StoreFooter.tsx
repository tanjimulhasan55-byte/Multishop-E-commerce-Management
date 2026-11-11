import { Link } from 'react-router-dom';
import { Logo } from './Logo';

const columns = [
  {
    title: 'Shop',
    links: [
      { to: '/products', label: 'All products' },
      { to: '/products?in_stock=1', label: 'In stock now' },
      { to: '/shops', label: 'Browse shops' },
    ],
  },
  {
    title: 'Account',
    links: [
      { to: '/account', label: 'Profile' },
      { to: '/account/orders', label: 'Orders' },
      { to: '/cart', label: 'Cart' },
    ],
  },
  {
    title: 'Sell',
    links: [
      { to: '/owner', label: 'Owner panel' },
      { to: '/owner/catalog', label: 'Shared catalog' },
    ],
  },
];

export function StoreFooter() {
  return (
    <footer className="mt-24 border-t border-line bg-surface">
      <div className="container-page grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
        <div className="lg:col-span-2">
          <Logo />
          <p className="mt-4 max-w-xs text-sm text-muted">
            A marketplace for independent shops across Bangladesh. Pay cash on delivery or online — every order goes
            straight to the seller.
          </p>
        </div>
        {columns.map((col) => (
          <div key={col.title}>
            <h2 className="text-xs font-semibold tracking-wider text-ink uppercase">{col.title}</h2>
            <ul className="mt-4 space-y-2.5">
              {col.links.map((l) => (
                <li key={l.to}>
                  <Link to={l.to} className="text-sm text-muted transition-colors hover:text-ink">
                    {l.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t border-line">
        <div className="container-page flex flex-col gap-2 py-6 text-[0.8125rem] text-muted sm:flex-row sm:justify-between">
          <p>© {new Date().getFullYear()} MultiShop. All prices in BDT (৳).</p>
          <p>Made in Dhaka</p>
        </div>
      </div>
    </footer>
  );
}
