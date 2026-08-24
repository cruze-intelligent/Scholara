@props(['message'])
<div {{ $attributes->merge(['class' => 'text-center py-8']) }}>
    <svg class="mx-auto h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-6-4h6m2 9H7a2 2 0 01-2-2V6a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V18a2 2 0 01-2 2z" />
    </svg>
    <p class="mt-2 text-sm text-gray-400">{{ $message }}</p>
</div>
