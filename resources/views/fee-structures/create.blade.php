<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Fee Structure') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('fee-structures.store') }}" class="space-y-4">
                    @csrf

                    <x-form.select name="curriculum_level" label="Curriculum level"
                        :options="collect($levels)->mapWithKeys(fn ($l) => [$l => ucwords(str_replace('_', ' ', $l))])" />
                    <x-form.input name="term" label="Term" placeholder="e.g. Term 2, 2026" />
                    <x-form.input name="label" label="Fee label" value="Tuition" />
                    <x-form.input name="amount" label="Amount (UGX)" type="number" />
                    <x-form.input name="due_date" label="Due date" type="date" />

                    <x-primary-button>{{ __('Add fee structure') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
