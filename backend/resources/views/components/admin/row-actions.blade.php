{{-- Row actions used inside DataTables cells (rendered server-side, escaped). --}}
@props(['show' => null, 'edit' => null, 'delete' => null, 'confirm' => 'Delete this item?'])
<div class="flex items-center justify-end gap-1">
    @if ($show)
        <a href="{{ $show }}" class="btn-icon" title="View"><x-icon name="eye" class="h-4 w-4" /></a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="btn-icon" title="Edit"><x-icon name="pencil" class="h-4 w-4" /></a>
    @endif
    @if ($delete)
        <form method="POST" action="{{ $delete }}" data-confirm="{{ $confirm }}" class="inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn-icon-danger" title="Delete"><x-icon name="trash" class="h-4 w-4" /></button>
        </form>
    @endif
</div>
