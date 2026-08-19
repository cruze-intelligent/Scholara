<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nurse Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Recent clinic visits</h3>
                @forelse ($recentVisits as $visit)
                    <div class="border-b border-gray-100 py-2 last:border-0">
                        <p class="font-medium">{{ $visit->student->full_name }} &mdash; {{ $visit->reason }}</p>
                        <p class="text-sm text-gray-500">{{ $visit->occurred_at->format('d M Y H:i') }} &middot; {{ $visit->outcome }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No clinic visits logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
