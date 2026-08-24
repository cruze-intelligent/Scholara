<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My Assessments') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                @forelse ($scores as $score)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center">
                        <div>
                            <p class="font-medium">{{ $score->assessment->subject->name }} &mdash; {{ $score->assessment->type }}</p>
                            <p class="text-sm text-gray-500">{{ $score->assessment->term }}</p>
                        </div>
                        <p class="font-medium">{{ $score->raw_score }} / {{ $score->assessment->max_score }}</p>
                    </div>
                @empty
                    <x-empty-state message="No scores recorded yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
