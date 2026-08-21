<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Report an Issue') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('incidents.store') }}" class="space-y-4">
                    @csrf

                    @if ($students->isNotEmpty())
                        <x-form.select name="student_id" label="Student (optional)"
                            :options="collect(['' => '—'])->merge($students->mapWithKeys(fn ($s) => [$s->id => $s->full_name]))" />
                    @endif

                    <x-form.select name="category" label="Category"
                        :options="['bullying' => 'Bullying', 'violence' => 'Violence', 'other' => 'Other']" />

                    <x-form.textarea name="description" label="What happened" />

                    <x-form.checkbox name="anonymous" label="Submit anonymously" />

                    <x-primary-button>{{ __('Submit report') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
