<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Inventory Item') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('inventory-items.store') }}" class="space-y-4">
                    @csrf
                    <x-form.select name="category" label="Category"
                        :options="['library' => 'Library', 'canteen' => 'Canteen', 'equipment' => 'Equipment']" />
                    <x-form.input name="name" label="Item name" />
                    <x-form.input name="quantity" label="Starting quantity" type="number" value="0" />
                    <x-form.input name="unit" label="Unit" placeholder="e.g. pieces, kg" />
                    <x-primary-button>{{ __('Add item') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
