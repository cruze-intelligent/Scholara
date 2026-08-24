<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Daily Activity Logs') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('daily-activity-logs.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New log
                </a>
            </div>

            <x-card>
                @forelse ($logs as $log)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <p class="font-medium">{{ $log->student->full_name }} &mdash; {{ $log->date->format('d M Y') }}</p>
                        <p class="text-sm text-gray-500">
                            Meals: {{ implode(', ', $log->meals ?? []) ?: '—' }}
                            &middot; Bathroom breaks: {{ $log->bathroom_breaks ?? 0 }}
                            &middot; Sleep: {{ implode(', ', $log->sleep_checks ?? []) ?: '—' }}
                        </p>
                        <p class="text-xs text-gray-400">Logged by {{ $log->loggedBy->name }}</p>
                    </div>
                @empty
                    <x-empty-state message="No activity logs yet." />
                @endforelse
            </x-card>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
