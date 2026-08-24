<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Payroll Run') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('payroll-runs.update', $payrollRun) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="period_start" label="Period start" type="date" :value="$payrollRun->period_start->toDateString()" />
                    <x-form.input name="period_end" label="Period end" type="date" :value="$payrollRun->period_end->toDateString()" />
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
