@props(['label', 'value', 'icon' => null, 'tone' => 'indigo'])

@php
$tones = [
    'indigo' => 'bg-indigo-50 text-indigo-600',
    'green' => 'bg-green-50 text-green-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'red' => 'bg-red-50 text-red-600',
];
@endphp

<div class="bg-white rounded-xl ring-1 ring-gray-950/5 shadow-sm p-4 flex items-center gap-3">
    @if ($icon)
        <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 {{ $tones[$tone] }}">
            <x-nav-icon :name="$icon" class="h-5 w-5" />
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-2xl font-semibold text-gray-800 leading-tight">{{ $value }}</p>
        <p class="text-xs text-gray-500 truncate">{{ $label }}</p>
    </div>
</div>
