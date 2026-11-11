<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }} Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800" x-data="{ sidebar: false }">
    <script type="application/json" id="flash-data">@json(['success' => session('success'), 'error' => session('error') ?? ($errors->any() && ! old() ? $errors->first() : null)])</script>

    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-slate-950 transition-transform duration-300 lg:translate-x-0"
           :class="sidebar && '!translate-x-0'">
        <div class="flex h-16 shrink-0 items-center gap-3 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 shadow-lg shadow-brand-900/40">
                <x-icon name="sparkles" class="h-5 w-5 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold tracking-tight text-white">{{ config('app.name') }}</p>
                <p class="text-[11px] font-medium text-slate-500">Admin Console</p>
            </div>
            <button class="ml-auto text-slate-400 lg:hidden" @click="sidebar = false"><x-icon name="x" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            @php($nav = [
                'Overview' => [
                    ['Dashboard', 'admin.dashboard', 'home', 'dashboard.view', 'admin.dashboard'],
                ],
                'Catalog' => [
                    ['Products', 'admin.products.index', 'cube', 'products.view', 'admin.products.*'],
                    ['Categories', 'admin.categories.index', 'folder', 'categories.view', 'admin.categories.*'],
                    ['Attributes', 'admin.attributes.index', 'swatch', 'attributes.view', 'admin.attributes.*'],
                ],
                'Commerce' => [
                    ['Shops', 'admin.shops.index', 'store', 'shops.view', 'admin.shops.*'],
                    ['Shop Listings', 'admin.listings.index', 'queue', 'listings.view', 'admin.listings.*'],
                    ['Orders', 'admin.orders.index', 'cart', 'orders.view', 'admin.orders.*'],
                ],
                'People & Access' => [
                    ['Shop Owners', ['admin.users.index', ['role' => 'shop_owner']], 'user-store', 'users.view', fn () => request()->routeIs('admin.users.*') && request('role') === 'shop_owner'],
                    ['All Users', 'admin.users.index', 'users', 'users.view', fn () => request()->routeIs('admin.users.*') && request('role') !== 'shop_owner'],
                    ['Roles & Permissions', 'admin.roles.index', 'shield', 'roles.view', 'admin.roles.*'],
                ],
            ])

            @foreach ($nav as $section => $items)
                @php($visible = collect($items)->filter(fn ($i) => auth()->user()->can($i[3])))
                @if ($visible->isNotEmpty())
                    <p class="nav-section">{{ $section }}</p>
                    <div class="space-y-1">
                        @foreach ($visible as [$label, $route, $icon, $permission, $pattern])
                            <a href="{{ is_array($route) ? route(...$route) : route($route) }}"
                               @class(['nav-link', 'active' => $pattern instanceof Closure ? $pattern() : request()->routeIs($pattern)])>
                                <x-icon :name="$icon" /> {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            @can('dashboard.view')
                <p class="nav-section">Developers</p>
                <a href="{{ url('/docs/api') }}" target="_blank" class="nav-link"><x-icon name="book" /> API Docs</a>
            @endcan
        </nav>

        <div class="border-t border-white/5 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->getRoleNames()->map(fn ($r) => Str::headline($r))->implode(', ') }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="lg:pl-72">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200/70 bg-white/80 px-4 backdrop-blur-xl sm:px-8">
            <button class="btn-icon lg:hidden" @click="sidebar = true" aria-label="Open sidebar"><x-icon name="menu" /></button>

            <nav class="hidden items-center gap-2 text-sm sm:flex" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-400 hover:text-slate-600"><x-icon name="home" class="h-4 w-4" /></a>
                @foreach ($breadcrumbs as $crumb)
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-slate-300" />
                    @if (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="font-medium text-slate-500 hover:text-slate-800">{{ $crumb['label'] }}</a>
                    @else
                        <span class="font-medium text-slate-800">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ config('services.frontend_url') }}" target="_blank" class="btn-ghost btn-sm hidden sm:inline-flex">
                    <x-icon name="globe" class="h-4 w-4" /> Storefront
                </a>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100" @click="open = !open">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                            {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right
                         class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl bg-white py-1 shadow-card ring-1 ring-slate-900/5">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                            <x-icon name="user-store" class="h-4 w-4" /> My profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50">
                                <x-icon name="logout" class="h-4 w-4" /> Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 sm:px-8">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
