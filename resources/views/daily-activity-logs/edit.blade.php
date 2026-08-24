<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Activity Log') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="text-sm text-gray-500 mb-4">{{ $log->student->full_name }} &mdash; {{ $log->date->format('d M Y') }}</p>
                <form method="POST" action="{{ route('daily-activity-logs.update', $log) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="meals" label="Meals (comma-separated)" :value="implode(', ', $log->meals ?? [])" />
                    <x-form.input name="bathroom_breaks" label="Bathroom breaks" type="number" :value="$log->bathroom_breaks" />
                    <x-form.input name="sleep_checks" label="Sleep checks (comma-separated times)" :value="implode(', ', $log->sleep_checks ?? [])" />
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
