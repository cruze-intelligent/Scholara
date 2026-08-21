<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Log Clinic Visit') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('clinic-visits.store') }}" class="space-y-4">
                    @csrf

                    <x-form.select name="student_id" label="Student"
                        :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />
                    <x-form.input name="reason" label="Reason for visit" />
                    <x-form.textarea name="diagnosis" label="Diagnosis (optional)" />
                    <x-form.textarea name="treatment" label="Treatment given (optional)" />
                    <x-form.select name="outcome" label="Outcome" :options="[
                        'returned_to_class' => 'Returned to class',
                        'sick_bay' => 'Sent to sick bay',
                        'referred_to_hospital' => 'Referred to hospital',
                    ]" />

                    <x-primary-button>{{ __('Log visit') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
