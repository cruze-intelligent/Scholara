<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">My scores</h3>
                @forelse (optional($student)->assessmentScores ?? [] as $score)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ $score->assessment->subject->name }} &mdash; {{ $score->assessment->type }}</span>
                        <span>{{ $score->raw_score }} / {{ $score->assessment->max_score }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No scores recorded yet.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Noticeboard</h3>
                @forelse ($notices as $notice)
                    <div class="border-b border-gray-100 py-2 last:border-0">
                        <p class="font-medium">{{ $notice->title }}</p>
                        <p class="text-sm text-gray-500">{{ $notice->body }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No notices yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
