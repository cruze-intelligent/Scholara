<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Invoice') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <form method="POST" action="{{ route('invoices.store') }}" class="space-y-4">
                    @csrf

                    <x-form.select name="student_id" label="Student" :options="$students->pluck('full_name', 'id')" />
                    <x-form.input name="term" label="Term" placeholder="Term 2 2026" />
                    <x-form.input name="amount_due" label="Amount due (UGX)" type="number" />
                    <x-form.input name="due_date" label="Due date" type="date" />

                    <x-primary-button type="submit">{{ __('Create invoice') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
