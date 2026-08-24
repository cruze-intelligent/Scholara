<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nurse Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <a href="{{ route('reports.health') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Health trends &rarr;
                </a>
            </div>

            <x-card title="Recent clinic visits">
                @forelse ($recentVisits as $visit)
                    <div class="border-b border-gray-100 py-2.5 last:border-0">
                        <p class="font-medium text-gray-800">{{ $visit->student->full_name }} &mdash; {{ $visit->reason }}</p>
                        <p class="text-sm text-gray-500">{{ $visit->occurred_at->format('d M Y H:i') }} &middot; {{ str_replace('_', ' ', $visit->outcome) }}</p>
                    </div>
                @empty
                    <x-empty-state message="No clinic visits logged yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
