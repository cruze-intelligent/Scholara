<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventory Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Inventory</h3>
                @forelse ($items as $item)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ $item->name }} ({{ $item->category }})</span>
                        <span>{{ $item->quantity }} {{ $item->unit }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No inventory items yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
