<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $inventoryItem->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm text-gray-500">{{ ucfirst($inventoryItem->category) }}</p>
                        <p class="text-2xl font-semibold text-gray-800">{{ $inventoryItem->quantity }} {{ $inventoryItem->unit }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('inventory-items.edit', $inventoryItem) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                        @if ($inventoryItem->transactions->isEmpty())
                            <form method="POST" action="{{ route('inventory-items.destroy', $inventoryItem) }}" onsubmit="return confirm('Delete this item?')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('inventory-items.transactions.store', $inventoryItem) }}" class="flex gap-2 items-center">
                    @csrf
                    <input type="number" name="quantity" min="1" value="1" class="w-20 text-sm border-gray-300 rounded-md">
                    <select name="type" class="text-sm border-gray-300 rounded-md">
                        <option value="in">Stock in</option>
                        <option value="out">Stock out</option>
                    </select>
                    <x-secondary-button type="submit">{{ __('Record') }}</x-secondary-button>
                </form>
            </x-card>

            <x-card title="Transaction history">
                @forelse ($inventoryItem->transactions as $transaction)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm {{ $transaction->voided_at ? 'opacity-50' : '' }}">
                        <span>
                            <x-badge :color="$transaction->type === 'in' ? 'green' : 'red'">
                                {{ $transaction->type === 'in' ? '+' : '-' }}{{ $transaction->quantity }}
                            </x-badge>
                            {{ $transaction->occurred_at->format('d M Y H:i') }}
                            @if ($transaction->voided_at) <span class="text-xs text-gray-400">(voided)</span> @endif
                        </span>
                        @if (! $transaction->voided_at)
                            <form method="POST" action="{{ route('inventory-items.transactions.void', [$inventoryItem, $transaction]) }}" onsubmit="return confirm('Void this transaction?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-gray-400 hover:text-red-600">Void</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <x-empty-state message="No transactions recorded yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
