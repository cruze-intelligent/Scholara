<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Report') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('incidents.update', $incident) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @if ($students->isNotEmpty())
                        <x-form.select name="student_id" label="Student (optional)" :selected="$incident->student_id"
                            :options="collect(['' => '—'])->merge($students->mapWithKeys(fn ($s) => [$s->id => $s->full_name]))" />
                    @endif

                    <x-form.select name="category" label="Category" :selected="$incident->category"
                        :options="['bullying' => 'Bullying', 'violence' => 'Violence', 'other' => 'Other']" />

                    <x-form.textarea name="description" label="What happened" :value="$incident->description" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
