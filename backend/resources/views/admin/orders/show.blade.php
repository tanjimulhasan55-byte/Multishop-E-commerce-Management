<x-admin-layout :title="'Order '.$order->number"
                :breadcrumbs="[['label' => 'Orders', 'url' => route('admin.orders.index')], ['label' => $order->number]]">
    <x-admin.page-header :title="'Order '.$order->number" :description="'Placed '.$order->placed_at->format('d M Y, H:i').' · '.($order->shop?->name ?? '—')">
        <x-slot:actions>
            <x-admin.badge :color="$order->status->color()">{{ $order->status->label() }}</x-admin.badge>
            <x-admin.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-admin.badge>
            <a href="{{ route('admin.orders.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Items" class="lg:col-span-2" :padded="false">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-6 py-3">Product</th><th class="px-6 py-3">SKU</th><th class="px-6 py-3 text-right">Price</th><th class="px-6 py-3 text-right">Qty</th><th class="px-6 py-3 text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-6 py-3.5">
                                <p class="font-semibold text-slate-900">{{ $item->product_name }}</p>
                                @if ($item->variant_label)<p class="text-xs text-slate-500">{{ $item->variant_label }}</p>@endif
                            </td>
                            <td class="px-6 py-3.5"><code class="text-xs text-slate-600">{{ $item->sku }}</code></td>
                            <td class="px-6 py-3.5 text-right tabular-nums">৳ {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-6 py-3.5 text-right tabular-nums">{{ $item->quantity }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold tabular-nums">৳ {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200">
                    <tr><td colspan="4" class="px-6 py-2 text-right text-slate-500">Subtotal</td><td class="px-6 py-2 text-right tabular-nums">৳ {{ number_format((float) $order->subtotal, 2) }}</td></tr>
                    <tr><td colspan="4" class="px-6 py-2 text-right text-slate-500">Shipping</td><td class="px-6 py-2 text-right tabular-nums">৳ {{ number_format((float) $order->shipping_total, 2) }}</td></tr>
                    <tr><td colspan="4" class="px-6 py-3 text-right font-semibold text-slate-900">Total</td><td class="px-6 py-3 text-right text-lg font-bold tabular-nums text-slate-900">৳ {{ number_format((float) $order->total, 2) }}</td></tr>
                </tfoot>
            </table>
        </x-admin.card>

        <div class="space-y-6">
            @can('updateStatus', $order)
                @if ($order->status->transitions())
                    <x-admin.card title="Update status">
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-4">
                            @csrf @method('PATCH')
                            <x-admin.select name="status" label="Move to" required
                                            :options="collect($order->status->transitions())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                            <button class="btn-primary w-full"><x-icon name="check" class="h-4 w-4" /> Update</button>
                        </form>
                    </x-admin.card>
                @endif
            @endcan
            <x-admin.card title="Customer">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Name</dt><dd class="font-medium text-slate-900">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ $order->customer_phone }}</dd></div>
                    <div><dt class="text-slate-500">Account</dt><dd class="font-medium text-slate-900">{{ $order->customer?->email ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Shipping address</dt><dd class="font-medium text-slate-900">{{ $order->shipping_address }}</dd></div>
                    @if ($order->notes)<div><dt class="text-slate-500">Notes</dt><dd class="text-slate-700">{{ $order->notes }}</dd></div>@endif
                </dl>
            </x-admin.card>
            <x-admin.card title="Payment">
                <p class="text-sm text-slate-700">{{ $order->payment_method->label() }}</p>
                @foreach ($order->payments as $payment)
                    <div class="mt-3 rounded-xl bg-slate-50 p-3 text-xs">
                        <p class="font-mono text-slate-700">{{ $payment->transaction_id }}</p>
                        <p class="mt-1 text-slate-500">{{ $payment->status->label() }} · ৳ {{ number_format((float) $payment->amount, 2) }} @if ($payment->gateway_reference) · ref {{ $payment->gateway_reference }} @endif</p>
                    </div>
                @endforeach
            </x-admin.card>
        </div>
    </div>
</x-admin-layout>
