<x-admin-layout title="Dashboard" :breadcrumbs="[['label' => 'Dashboard']]">
    <x-admin.page-header :title="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.Str::before(auth()->user()->name, ' ')"
                         description="Here's what's happening across the marketplace today." />

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Paid revenue" :value="'৳ '.number_format($stats['revenue'])" icon="banknotes" tone="emerald" hint="Across all shops" />
        <x-admin.stat label="Orders" :value="number_format($stats['orders'])" icon="cart" tone="brand"
                      :hint="$stats['pending'].' pending · '.$stats['customers'].' '.Str::plural('customer', $stats['customers'])" />
        <x-admin.stat label="Shops" :value="$stats['shops']" icon="store" tone="sky" :hint="$stats['activeShops'].' active · '.$stats['owners'].' owners'" />
        <x-admin.stat label="Catalog products" :value="$stats['products']" icon="cube" tone="amber" :hint="$stats['unlisted'].' not listed by any shop'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-admin.card title="Sales — last 14 days" description="Orders and revenue (excluding cancelled)" class="xl:col-span-2">
            <div class="h-72"><canvas id="salesChart"></canvas></div>
        </x-admin.card>
        <x-admin.card title="Orders by status">
            <div class="mx-auto h-56 max-w-[16rem]"><canvas id="statusChart"></canvas></div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                @foreach (App\Enums\OrderStatus::cases() as $status)
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="flex items-center gap-2 text-slate-600"><span class="h-2 w-2 rounded-full bg-{{ $status->color() }}-500"></span>{{ $status->label() }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ $statusBreakdown[$status->value] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-admin.card title="Latest orders" class="xl:col-span-2" :padded="false">
            <x-slot:actions>
                @can('viewAny', App\Models\Order::class)<a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">View all</a>@endcan
            </x-slot:actions>
            <div class="divide-y divide-slate-100">
                @forelse ($latestOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-slate-50/70">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100"><x-icon name="cart" class="h-5 w-5 text-slate-500" /></div>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->number }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $order->customer_name }} · {{ $order->shop?->name }}</p>
                        </div>
                        <x-admin.badge :color="$order->status->color()">{{ $order->status->label() }}</x-admin.badge>
                        <p class="w-28 text-right font-semibold tabular-nums text-slate-900">৳ {{ number_format((float) $order->total, 2) }}</p>
                    </a>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">No orders yet.</p>
                @endforelse
            </div>
        </x-admin.card>
        <x-admin.card title="Low stock" description="Active variants with fewer than 5 units" :padded="false">
            <div class="divide-y divide-slate-100">
                @forelse ($lowStock as $sv)
                    <div class="flex items-center gap-3 px-6 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $sv->listing?->displayName() }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $sv->variant?->label() ?? $sv->variant?->sku }} · {{ $sv->listing?->shop?->name }}</p>
                        </div>
                        <x-admin.badge :color="$sv->stock === 0 ? 'rose' : 'amber'">{{ $sv->stock === 0 ? 'Out' : $sv->stock.' left' }}</x-admin.badge>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">All stocked up. 🎉</p>
                @endforelse
            </div>
        </x-admin.card>
    </div>

    @push('scripts')
        <script type="module">
            const chart = @json($chart);
            const ctx = document.getElementById('salesChart');
            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, 'rgba(99,102,241,0.25)');
            gradient.addColorStop(1, 'rgba(99,102,241,0)');

            new Chart(ctx, {
                data: {
                    labels: chart.labels,
                    datasets: [
                        { type: 'line', label: 'Revenue (৳)', data: chart.revenue, borderColor: '#6366f1', backgroundColor: gradient, fill: true, tension: 0.4, yAxisID: 'y', pointRadius: 0, pointHoverRadius: 5, borderWidth: 2.5 },
                        { type: 'bar', label: 'Orders', data: chart.orders, backgroundColor: 'rgba(16,185,129,0.6)', borderRadius: 6, yAxisID: 'y1', maxBarThickness: 18 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                        y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' }, beginAtZero: true },
                        y1: { position: 'right', grid: { display: false }, ticks: { color: '#94a3b8', precision: 0 }, beginAtZero: true },
                    },
                },
            });

            const statuses = @json(collect(App\Enums\OrderStatus::cases())->map(fn ($s) => ['label' => $s->label(), 'value' => $statusBreakdown[$s->value] ?? 0]));
            const palette = { Pending: '#f59e0b', Processing: '#0ea5e9', Shipped: '#6366f1', Delivered: '#10b981', Cancelled: '#f43f5e' };
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: { labels: statuses.map(s => s.label), datasets: [{ data: statuses.map(s => s.value), backgroundColor: statuses.map(s => palette[s.label]), borderWidth: 0 }] },
                options: { cutout: '72%', plugins: { legend: { display: false } } },
            });
        </script>
    @endpush
</x-admin-layout>
