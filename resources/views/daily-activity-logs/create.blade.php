<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Activity Log') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($students->isEmpty())
                    <p class="text-gray-500">No nursery-level students found.</p>
                @else
                    <form method="POST" action="{{ route('daily-activity-logs.store') }}" class="space-y-4">
                        @csrf
                        <x-form.select name="student_id" label="Student"
                            :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />
                        <x-form.input name="date" label="Date" type="date" :value="now()->toDateString()" />
                        <x-form.input name="meals" label="Meals (comma-separated)" placeholder="breakfast, lunch, snack" />
                        <x-form.input name="bathroom_breaks" label="Bathroom breaks" type="number" value="0" />
                        <x-form.input name="sleep_checks" label="Sleep checks (comma-separated times)" placeholder="12:30, 13:00" />
                        <x-primary-button>{{ __('Save log') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
