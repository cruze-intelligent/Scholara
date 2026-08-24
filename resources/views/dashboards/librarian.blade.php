<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventory Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <a href="{{ route('inventory-items.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Manage inventory &rarr;
                </a>
            </div>

            <x-card title="Inventory">
                @forelse ($items as $item)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm">
                        <span class="text-gray-700">{{ $item->name }} <span class="text-gray-400">({{ $item->category }})</span></span>
                        <x-badge :color="$item->quantity < 10 ? 'red' : 'gray'">{{ $item->quantity }} {{ $item->unit }}</x-badge>
                    </div>
                @empty
                    <x-empty-state message="No inventory items yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
