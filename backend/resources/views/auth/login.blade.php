<x-guest-layout>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h1>
    <p class="mt-2 text-sm text-slate-500">Sign in to manage shops, catalog and access.</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <x-admin.input name="email" type="email" label="Email address" required autofocus autocomplete="username" placeholder="admin@multishop.com" />
        <x-admin.input name="password" type="password" label="Password" required autocomplete="current-password" />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="form-checkbox"> Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full py-3">Sign in</button>
    </form>

    @if (app()->isLocal())
        <div class="mt-8 rounded-2xl bg-slate-50 p-4 text-xs text-slate-600 ring-1 ring-slate-100">
            <p class="font-semibold text-slate-800">Demo accounts (password: <code>password</code>)</p>
            <p class="mt-1"><code>admin@multishop.com</code> — full access</p>
            <p><code>manager@multishop.com</code> — custom "Catalog Manager" role (limited)</p>
        </div>
    @endif
</x-guest-layout>
