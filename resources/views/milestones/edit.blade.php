<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Milestone') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="text-sm text-gray-500 mb-4">{{ $checklist->student->full_name }}</p>
                <form method="POST" action="{{ route('milestones.update', $checklist) }}" class="space-y-4" x-data="{ domain: '{{ $checklist->domain }}' }">
                    @csrf
                    @method('PUT')
                    <x-form.select name="domain" label="Domain" x-model="domain"
                        :options="['physical' => 'Physical', 'cognitive' => 'Cognitive', 'emotional' => 'Emotional', 'health' => 'Health']" />

                    <div>
                        <x-input-label for="milestone_label" value="Milestone" />
                        <select id="milestone_label" name="milestone_label" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($catalog as $domainKey => $labels)
                                <optgroup label="{{ ucfirst($domainKey) }}" x-show="domain === '{{ $domainKey }}'">
                                    @foreach ($labels as $label)
                                        <option value="{{ $label }}" @selected($checklist->milestone_label === $label)>{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <x-form.input name="achieved_at" label="Achieved on (optional)" type="date" :value="$checklist->achieved_at?->toDateString()" />
                    <x-form.textarea name="notes" label="Notes (optional)" :value="$checklist->notes" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
