<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Inventory Item') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('inventory-items.store') }}"
                    x-data="{ category: '{{ old('category', 'library') }}' }" class="space-y-4">
                    @csrf
                    <x-form.select name="category" label="Category" x-model="category"
                        :options="['library' => 'Library', 'canteen' => 'Canteen', 'equipment' => 'Equipment']" />
                    <x-form.input name="name" label="{{ __('Item name') }}" />

                    <div x-show="category === 'library'" x-cloak class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                        <div class="col-span-2"><x-form.input name="author" label="Author" /></div>
                        <x-form.input name="isbn" label="ISBN" />
                        <x-form.input name="publisher" label="Publisher" />
                        <x-form.input name="edition_year" label="Edition year" type="number" />
                        <x-form.input name="shelf_location" label="Shelf location" placeholder="e.g. Fiction A3" />
                    </div>

                    <x-form.input name="quantity" label="Starting quantity" type="number" value="0" />
                    <x-form.input name="unit" label="Unit" placeholder="e.g. pieces, kg" />
                    <x-primary-button>{{ __('Add item') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
