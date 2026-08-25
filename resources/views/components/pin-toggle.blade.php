@props(['pinKey', 'label' => 'Pin to dashboard'])
@php $pinned = auth()->user()->hasPinned($pinKey); @endphp
<form method="POST" action="{{ $pinned ? route('pins.destroy', $pinKey) : route('pins.store', $pinKey) }}" class="inline">
    @csrf
    @if ($pinned) @method('DELETE') @endif
    <button type="submit" title="{{ $pinned ? 'Unpin from dashboard' : $label }}"
        class="inline-flex items-center gap-1 text-xs font-medium transition-colors {{ $pinned ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600' }}">
        <x-nav-icon name="pin" class="h-3.5 w-3.5" />
        {{ $pinned ? 'Pinned' : 'Pin' }}
    </button>
</form>
