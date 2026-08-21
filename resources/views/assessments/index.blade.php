<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessments') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('assessments.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New assessment
                </a>
            </div>

            <x-card>
                @forelse ($assessments as $assessment)
                    <a href="{{ route('assessments.show', $assessment) }}"
                       class="block border-b border-gray-100 py-3 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $assessment->subject->name }} &mdash; {{ $assessment->schoolClass->name }}</p>
                                <p class="text-sm text-gray-500">{{ $assessment->term }} &middot; max {{ $assessment->max_score }}</p>
                            </div>
                            <x-badge color="blue">{{ $assessment->type }}</x-badge>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500">No assessments created yet.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
