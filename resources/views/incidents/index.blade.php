<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Issue Reports') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('incidents.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Report an issue
                </a>
            </div>

            <x-card>
                @forelse ($incidents as $incident)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">
                                    {{ $incident->student->full_name ?? 'No student linked' }}
                                    <x-badge color="gray">{{ $incident->category }}</x-badge>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">{{ $incident->description }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $incident->anonymous ? 'Anonymous report' : ($incident->reporter->name ?? 'Unknown reporter') }}
                                    @if ($incident->assignedTo) &middot; assigned to {{ $incident->assignedTo->name }} @endif
                                </p>
                            </div>
                            @if (auth()->user()->hasAnyRole(['admin', 'teacher', 'nurse']))
                                <form method="POST" action="{{ route('incidents.status', $incident) }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="text-xs border-gray-300 rounded-md">
                                        @foreach (['open', 'in_review', 'resolved'] as $status)
                                            <option value="{{ $status }}" @selected($incident->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <x-badge :color="$incident->status === 'resolved' ? 'green' : 'yellow'">
                                    {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                                </x-badge>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No reports yet.</p>
                @endforelse
            </x-card>

            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
