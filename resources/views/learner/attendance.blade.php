<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My Attendance') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                @forelse ($records as $record)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between items-center text-sm">
                        <span>{{ $record->date->format('d M Y') }}</span>
                        <x-badge :color="match($record->status) { 'present' => 'green', 'late' => 'yellow', default => 'red' }">
                            {{ ucfirst($record->status) }}
                        </x-badge>
                    </div>
                @empty
                    <p class="text-gray-500">No attendance recorded yet.</p>
                @endforelse
            </x-card>

            {{ $records->links() }}
        </div>
    </div>
</x-app-layout>
