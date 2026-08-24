<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Clinic Visit') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="text-sm text-gray-500 mb-4">{{ $visit->student->full_name }}</p>
                <form method="POST" action="{{ route('clinic-visits.update', $visit) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="reason" label="Reason for visit" :value="$visit->reason" />
                    <x-form.textarea name="diagnosis" label="Diagnosis (optional)" :value="$visit->diagnosis" />
                    <x-form.textarea name="treatment" label="Treatment given (optional)" :value="$visit->treatment" />
                    <x-form.select name="outcome" label="Outcome" :selected="$visit->outcome" :options="[
                        'returned_to_class' => 'Returned to class',
                        'sick_bay' => 'Sent to sick bay',
                        'referred_to_hospital' => 'Referred to hospital',
                    ]" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
