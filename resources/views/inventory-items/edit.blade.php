<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Item') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('inventory-items.update', $inventoryItem) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.select name="category" label="Category" :selected="$inventoryItem->category"
                        :options="['library' => 'Library', 'canteen' => 'Canteen', 'equipment' => 'Equipment']" />
                    <x-form.input name="name" label="Name" :value="$inventoryItem->name" />
                    <x-form.input name="unit" label="Unit" :value="$inventoryItem->unit" />
                    <p class="text-sm text-gray-500">
                        Quantity ({{ $inventoryItem->quantity }} {{ $inventoryItem->unit }}) isn't edited here — record a
                        stock in/out transaction from the item's page instead, so the transaction history stays accurate.
                    </p>

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
