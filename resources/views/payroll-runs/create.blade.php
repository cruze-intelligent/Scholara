<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Payroll Run') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('payroll-runs.store') }}" class="space-y-4">
                    @csrf
                    <x-form.input name="period_start" label="Period start" type="date" />
                    <x-form.input name="period_end" label="Period end" type="date" />
                    <x-primary-button>{{ __('Create run') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
