<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Record a Milestone') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($students->isEmpty())
                    <p class="text-gray-500">No nursery-level students found.</p>
                @else
                    <form method="POST" action="{{ route('milestones.store') }}" class="space-y-4" x-data="{ domain: 'physical' }">
                        @csrf
                        <x-form.select name="student_id" label="Student"
                            :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />

                        <x-form.select name="domain" label="Domain" x-model="domain"
                            :options="['physical' => 'Physical', 'cognitive' => 'Cognitive', 'emotional' => 'Emotional', 'health' => 'Health']" />

                        <div>
                            <x-input-label for="milestone_label" value="Milestone" />
                            <select id="milestone_label" name="milestone_label" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($catalog as $domainKey => $labels)
                                    <optgroup label="{{ ucfirst($domainKey) }}" x-show="domain === '{{ $domainKey }}'">
                                        @foreach ($labels as $label)
                                            <option value="{{ $label }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <x-form.input name="achieved_at" label="Achieved on (optional)" type="date" />
                        <x-form.textarea name="notes" label="Notes (optional)" />

                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
