<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $assessment->subject->name }} &mdash; {{ $assessment->schoolClass->name }} ({{ $assessment->type }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <div class="flex justify-between text-sm text-gray-500 mb-4">
                    <span>{{ $assessment->term }} &middot; max score {{ $assessment->max_score }} &middot; weight {{ $assessment->weight }}</span>
                    <span>Class mean: {{ $classMean !== null ? round($classMean, 1) : '—' }}</span>
                </div>

                <form method="POST" action="{{ route('assessments.scores.store', $assessment) }}" class="space-y-1">
                    @csrf
                    <div class="grid grid-cols-3 gap-2 text-xs font-semibold text-gray-500 uppercase border-b pb-2">
                        <span class="col-span-2">Student</span>
                        <span>Raw score</span>
                    </div>
                    @forelse ($students as $student)
                        @php $existing = $existingScores->get($student->id) @endphp
                        <div class="grid grid-cols-3 gap-2 items-center py-2 border-b border-gray-100 last:border-0">
                            <span class="col-span-2">{{ $student->full_name }}</span>
                            <input type="number" step="0.01" min="0" max="{{ $assessment->max_score }}"
                                name="scores[{{ $student->id }}]"
                                value="{{ old('scores.'.$student->id, $existing?->raw_score) }}"
                                class="border-gray-300 rounded-md shadow-sm w-28 text-sm">
                        </div>
                    @empty
                        <p class="text-gray-500 py-4">No students in this class yet.</p>
                    @endforelse

                    @if ($students->isNotEmpty())
                        <div class="pt-4">
                            <x-primary-button>{{ __('Save scores') }}</x-primary-button>
                        </div>
                    @endif
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
