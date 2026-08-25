<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Timetable') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (auth()->user()->hasRole('admin'))
                <div class="flex justify-end">
                    <a href="{{ route('periods.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        + Schedule a period
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach ($days as $day)
                    <x-card class="!p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ ucfirst($day) }}</p>
                        @forelse ($periods->get($day, collect()) as $period)
                            <div class="border-b border-gray-100 py-2 last:border-0 text-sm">
                                <p class="font-medium text-gray-800">{{ $period->subject?->name }}</p>
                                <p class="text-xs text-gray-500">{{ $period->schoolClass?->name }} &middot; {{ $period->teacher?->name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ \Illuminate\Support\Carbon::parse($period->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($period->end_time)->format('H:i') }}
                                    @if ($period->room) &middot; {{ $period->room }} @endif
                                </p>
                                @if (auth()->user()->hasRole('admin'))
                                    <form method="POST" action="{{ route('periods.destroy', $period) }}" onsubmit="return confirm('Remove this period?')" class="mt-1">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-medium text-red-500 hover:text-red-700">Remove</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No periods.</p>
                        @endforelse
                    </x-card>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
