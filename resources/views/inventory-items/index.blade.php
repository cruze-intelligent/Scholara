<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Inventory') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('inventory-items.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New item
                </a>
            </div>

            <x-card>
                @forelse ($items as $item)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <p class="font-medium">{{ $item->name }}</p>
                                <p class="text-sm text-gray-500">{{ ucfirst($item->category) }} &middot; {{ $item->quantity }} {{ $item->unit }}</p>
                            </div>
                            <form method="POST" action="{{ route('inventory-items.transactions.store', $item) }}" class="flex gap-2 items-center">
                                @csrf
                                <input type="number" name="quantity" min="1" value="1" class="w-16 text-sm border-gray-300 rounded-md">
                                <select name="type" class="text-sm border-gray-300 rounded-md">
                                    <option value="in">In</option>
                                    <option value="out">Out</option>
                                </select>
                                <x-secondary-button type="submit">{{ __('Record') }}</x-secondary-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No inventory items yet.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
