import Alpine from 'alpinejs';
import jQuery from 'jquery';
import DataTable from 'datatables.net-dt';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';
import TomSelect from 'tom-select';

window.$ = window.jQuery = jQuery;
window.DataTable = DataTable;
window.Swal = Swal;
window.Chart = Chart;
window.TomSelect = TomSelect;
window.Alpine = Alpine;

const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

/* ---------------- Toasts ---------------- */
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    customClass: { popup: '!rounded-2xl !shadow-card' },
});
window.toast = (icon, title) => Toast.fire({ icon, title });

/* ---------------- Confirm-before-submit ----------------
 * <form data-confirm="Delete this shop?"> ... </form>
 * Works for server-rendered forms and forms inside DataTables rows.
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) {
        return;
    }
    event.preventDefault();

    const result = await Swal.fire({
        title: form.dataset.confirm,
        text: form.dataset.confirmText || 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: '!rounded-3xl',
            confirmButton: 'btn-danger ml-2',
            cancelButton: 'btn-secondary',
        },
    });

    if (result.isConfirmed) {
        form.dataset.confirmed = '1';
        form.submit();
    }
});

/* ---------------- DataTables helper ----------------
 * <table data-datatable data-url="..." data-columns='[...]' data-order='[[0,"desc"]]'>
 * Extra filters: any element with [data-dt-filter="name"] inside [data-dt-filters="#tableId"]
 */
window.initDataTable = (selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;

    const filters = () => {
        const data = {};
        document.querySelectorAll(`[data-dt-filters="${selector}"] [data-dt-filter]`).forEach((f) => {
            if (f.value !== '') data[f.dataset.dtFilter] = f.value;
        });
        return data;
    };

    const table = new DataTable(el, {
        processing: true,
        serverSide: true,
        searchDelay: 350,
        pageLength: Number(el.dataset.pageLength || 10),
        lengthMenu: [10, 25, 50, 100],
        order: JSON.parse(el.dataset.order || '[[0, "desc"]]'),
        ajax: {
            url: el.dataset.url,
            data: (d) => Object.assign(d, filters()),
        },
        columns: JSON.parse(el.dataset.columns),
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            lengthMenu: '_MENU_ per page',
            emptyTable: 'Nothing here yet.',
            zeroRecords: 'No matching records.',
            processing: 'Loading…',
        },
        layout: {
            topStart: 'search',
            topEnd: 'pageLength',
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
    });

    document.querySelectorAll(`[data-dt-filters="${selector}"] [data-dt-filter]`).forEach((f) =>
        f.addEventListener('change', () => table.ajax.reload()),
    );

    return table;
};

/* ---------------- Tom Select auto-init ---------------- */
const initSelects = () => {
    document.querySelectorAll('select[data-tom-select]:not(.tomselected)').forEach((el) => {
        new TomSelect(el, {
            plugins: el.multiple ? ['remove_button'] : [],
            allowEmptyOption: true,
            maxOptions: 500,
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initSelects();

    // Flash messages rendered by the layout.
    const flash = document.getElementById('flash-data');
    if (flash) {
        const { success, error } = JSON.parse(flash.textContent || '{}');
        if (success) window.toast('success', success);
        if (error) window.toast('error', error);
    }
});

window.csrf = csrf;

Alpine.start();
