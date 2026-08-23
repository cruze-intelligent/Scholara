@props(['title' => null])
<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden rounded-xl ring-1 ring-gray-950/5 shadow-sm p-6']) }}>
    @if ($title)
        <h3 class="font-semibold text-gray-800 mb-4">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
