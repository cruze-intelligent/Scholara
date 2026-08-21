<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Log Medication Administration') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('medications.store') }}" class="space-y-4">
                    @csrf

                    <x-form.select name="student_id" label="Student"
                        :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />
                    <x-form.input name="medication_name" label="Medication" />
                    <x-form.input name="dose" label="Dose" placeholder="e.g. 5ml" />
                    <x-form.input name="route" label="Route" placeholder="e.g. oral, topical, inhaled" />
                    <x-form.input name="scheduled_time" label="Scheduled time (optional)" type="datetime-local" />
                    <x-form.textarea name="notes" label="Notes (optional)" />

                    <div class="border border-gray-200 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Five Rights check — all required before saving</p>
                        <div class="space-y-2">
                            @foreach (\App\Models\MedicationAdministration::RIGHTS as $field => $label)
                                <x-form.checkbox :name="$field" :label="$label" required />
                            @endforeach
                        </div>
                    </div>

                    <x-primary-button>{{ __('Log administration') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
