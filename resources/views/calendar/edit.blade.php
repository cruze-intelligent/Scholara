<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Calendar Date') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <form method="POST" action="{{ route('calendar.update', $event) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <x-form.input name="title" label="Title" :value="$event->title" />
                    <x-form.select name="category" label="Category" :options="$categories" :selected="$event->category" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-form.input name="start_date" label="Start date" type="date" :value="$event->start_date->toDateString()" />
                        <x-form.input name="end_date" label="End date (optional)" type="date" :value="$event->end_date?->toDateString()" />
                    </div>
                    <x-form.textarea name="description" label="Notes (optional)" :value="$event->description" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>

            <form method="POST" action="{{ route('calendar.destroy', $event) }}" onsubmit="return confirm('Remove this calendar date?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Remove this date</button>
            </form>
        </div>
    </div>
</x-app-layout>
