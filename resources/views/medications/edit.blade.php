<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Medication Record') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="text-sm text-gray-500 mb-4">{{ $administration->student->full_name }}</p>
                <form method="POST" action="{{ route('medications.update', $administration) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="medication_name" label="Medication" :value="$administration->medication_name" />
                    <x-form.input name="dose" label="Dose" :value="$administration->dose" />
                    <x-form.input name="route" label="Route" :value="$administration->route" />
                    <x-form.textarea name="notes" label="Notes (optional)" :value="$administration->notes" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
