<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Calendar Date') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('calendar.store') }}" class="space-y-4">
                    @csrf

                    <x-form.input name="title" label="Title" placeholder="e.g. Term 2 begins" />
                    <x-form.select name="category" label="Category" :options="$categories" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-form.input name="start_date" label="Start date" type="date" />
                        <x-form.input name="end_date" label="End date (optional)" type="date" />
                    </div>
                    <x-form.textarea name="description" label="Notes (optional)" />

                    <x-primary-button>{{ __('Add to calendar') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
