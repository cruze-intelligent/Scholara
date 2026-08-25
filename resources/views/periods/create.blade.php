<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Schedule a Period') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($assignments->isEmpty())
                    <p class="text-gray-500">No teacher/subject/class assignments exist yet — set those up first.</p>
                @else
                    <form method="POST" action="{{ route('periods.store') }}" class="space-y-4">
                        @csrf

                        <x-form.select name="teacher_subject_assignment_id" label="Teacher / Subject / Class"
                            :options="$assignments->mapWithKeys(fn ($a) => [$a->id => \"{$a->teacher->name} — {$a->subject->name} — {$a->schoolClass->name}\"])" />

                        <x-form.select name="day_of_week" label="Day"
                            :options="collect($days)->mapWithKeys(fn ($d) => [$d => ucfirst($d)])" />

                        <div class="grid grid-cols-2 gap-4">
                            <x-form.input name="start_time" label="Start time" type="time" />
                            <x-form.input name="end_time" label="End time" type="time" />
                        </div>

                        <x-form.input name="room" label="Room (optional)" placeholder="e.g. Room 4" />

                        <x-primary-button>{{ __('Schedule') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
