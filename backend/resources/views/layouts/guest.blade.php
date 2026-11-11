<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <div class="flex min-h-full">
        <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:w-[34rem] xl:px-20">
            <div class="mx-auto w-full max-w-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-violet-600 shadow-lg shadow-brand-500/30">
                        <x-icon name="sparkles" class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-lg font-bold tracking-tight">{{ config('app.name') }}</p>
                        <p class="text-xs font-medium text-slate-500">Admin Console</p>
                    </div>
                </div>
                <div class="mt-10">{{ $slot }}</div>
            </div>
        </div>
        <div class="relative hidden flex-1 overflow-hidden bg-slate-950 lg:block">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,_var(--tw-gradient-stops))] from-brand-600/50 via-slate-950 to-slate-950"></div>
            <div class="absolute -bottom-32 -right-32 h-[32rem] w-[32rem] rounded-full bg-violet-600/30 blur-3xl"></div>
            <div class="relative flex h-full flex-col justify-end p-16 text-white">
                <p class="text-sm font-semibold uppercase tracking-widest text-brand-300">Multi-shop commerce</p>
                <h2 class="mt-4 max-w-lg text-4xl font-bold leading-tight tracking-tight">One shared catalog.<br>Every shop, its own price.</h2>
                <p class="mt-4 max-w-md text-slate-400">Manage shops, owners, products with variants and SKUs, and granular role-based access — from one place.</p>
                <div class="mt-10 grid max-w-md grid-cols-3 gap-4 text-sm">
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10"><p class="text-2xl font-bold">EN·বাং</p><p class="text-slate-400">Bilingual</p></div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10"><p class="text-2xl font-bold">RBAC</p><p class="text-slate-400">Custom roles</p></div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10"><p class="text-2xl font-bold">SKU</p><p class="text-slate-400">Per variant</p></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
