@props(['active' => false, 'icon' => null])

@php
$classes = $active
    ? 'flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 shadow-sm shadow-indigo-600/20 transition-colors duration-150'
    : 'flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-900/5 transition-colors duration-150';
$iconClasses = $active ? 'text-white' : 'text-gray-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        <x-nav-icon :name="$icon" :class="$iconClasses" />
    @endif
    {{ $slot }}
</a>
