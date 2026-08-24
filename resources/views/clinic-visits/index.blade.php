<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Clinic Visits') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('clinic-visits.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Log visit
                </a>
            </div>

            <x-card>
                @forelse ($visits as $visit)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <p class="font-medium">{{ $visit->student->full_name }} &mdash; {{ $visit->reason }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $visit->occurred_at->format('d M Y H:i') }} &middot; {{ str_replace('_', ' ', $visit->outcome) }}
                            &middot; logged by {{ $visit->loggedBy->name }}
                        </p>
                        @if ($visit->diagnosis)
                            <p class="text-sm text-gray-500 mt-1">Diagnosis: {{ $visit->diagnosis }}</p>
                        @endif
                    </div>
                @empty
                    <x-empty-state message="No clinic visits logged yet." />
                @endforelse
            </x-card>

            {{ $visits->links() }}
        </div>
    </div>
</x-app-layout>
