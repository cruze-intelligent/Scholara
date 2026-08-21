<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Health Record') }} &mdash; {{ $student->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600 mb-4">{{ session('status') }}</p>
            @endif

            <x-card>
                <form method="POST" action="{{ route('health-records.update', $student) }}" class="space-y-4">
                    @csrf @method('PUT')

                    <x-form.textarea name="chronic_conditions" label="Chronic conditions (comma-separated)"
                        :value="implode(', ', $healthRecord->chronic_conditions ?? [])" />
                    <x-form.textarea name="allergies" label="Allergies (comma-separated)"
                        :value="implode(', ', $healthRecord->allergies ?? [])" />
                    <x-form.textarea name="vaccinations" label="Vaccinations (comma-separated)"
                        :value="implode(', ', $healthRecord->vaccinations ?? [])" />
                    <x-form.input name="family_physician" label="Family physician"
                        :value="$healthRecord->family_physician['name'] ?? ''" />

                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
