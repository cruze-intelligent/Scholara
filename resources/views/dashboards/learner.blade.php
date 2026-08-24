<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">My scores</h3>
                    <a href="{{ route('learner.assessments') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        View all &rarr;
                    </a>
                </div>
                @forelse (optional($student)->assessmentScores ?? [] as $score)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm">
                        <span class="text-gray-700">{{ $score->assessment->subject->name }} <span class="text-gray-400">— {{ $score->assessment->type }}</span></span>
                        <span class="font-medium text-gray-800">{{ $score->raw_score }} / {{ $score->assessment->max_score }}</span>
                    </div>
                @empty
                    <x-empty-state message="No scores recorded yet." />
                @endforelse

                @if ($subjectPredictions->isNotEmpty())
                    <p class="text-sm font-medium text-gray-500 mt-4 mb-2">Performance trend</p>
                    @foreach ($subjectPredictions as $prediction)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                            <span class="text-gray-700">{{ $prediction['subject']->name }}</span>
                            <span class="font-medium text-gray-800">{{ $prediction['predicted'] !== null ? round($prediction['predicted'], 1) : '—' }}</span>
                        </div>
                    @endforeach
                @endif
            </x-card>

            <x-card>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-800">Noticeboard</h3>
                    <div class="flex gap-4">
                        <a href="{{ route('learner.attendance') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            My attendance
                        </a>
                        <a href="{{ route('learner.notices') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            View all &rarr;
                        </a>
                    </div>
                </div>
                @forelse ($notices as $notice)
                    <div class="border-b border-gray-100 py-2.5 last:border-0">
                        <p class="font-medium text-gray-800">{{ $notice->title }}</p>
                        <p class="text-sm text-gray-500">{{ $notice->body }}</p>
                    </div>
                @empty
                    <x-empty-state message="No notices yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
