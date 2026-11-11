{{--
    Server-side (yajra) DataTable inside a card, with an optional filter bar.

    <x-admin.datatable id="shops-table" :url="route('admin.shops.data')" :order="[[4, 'desc']]" :columns="[
        ['data' => 'shop', 'title' => 'Shop'],
        ['data' => 'status', 'title' => 'Status', 'searchable' => false],
        ['data' => 'actions', 'title' => '', 'orderable' => false, 'searchable' => false],
    ]">
        <x-slot:filters> <select data-dt-filter="status">…</select> </x-slot:filters>
    </x-admin.datatable>
--}}
@props(['id', 'url', 'columns', 'order' => [[0, 'asc']], 'pageLength' => 10])
@php
    $columns = collect($columns)->map(fn (array $c) => array_merge(['name' => $c['data']], $c, $c['data'] === 'actions'
        ? ['orderable' => false, 'searchable' => false, 'className' => 'text-right', 'title' => $c['title'] ?? '']
        : []))->values()->all();
@endphp
<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @isset($filters)
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-6 py-4" data-dt-filters="#{{ $id }}">
            {{ $filters }}
        </div>
    @endisset
    <table id="{{ $id }}" class="w-full"
           data-url="{{ $url }}"
           data-order="{{ json_encode($order) }}"
           data-page-length="{{ $pageLength }}"
           data-columns="{{ json_encode($columns) }}"></table>
</div>

@push('scripts')
    <script type="module">initDataTable('#{{ $id }}');</script>
@endpush
