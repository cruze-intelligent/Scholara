<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Request a Gate Pass') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($students->isEmpty())
                    <p class="text-gray-500">No students available to request a pass for.</p>
                @else
                    <form method="POST" action="{{ route('gate-passes.store') }}" class="space-y-4">
                        @csrf

                        <x-form.select name="student_id" label="Student"
                            :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />

                        <x-form.textarea name="reason" label="Reason" placeholder="e.g. Medical appointment at 2pm" />

                        <x-primary-button>{{ __('Submit request') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
