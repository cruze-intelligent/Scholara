<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Milestone Checklists') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('milestones.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Record milestone
                </a>
            </div>

            <x-card>
                @forelse ($checklists as $checklist)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $checklist->student->full_name }} &mdash; {{ $checklist->milestone_label }}</p>
                                <p class="text-sm text-gray-500">{{ $checklist->notes }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <x-badge color="blue">{{ ucfirst($checklist->domain) }}</x-badge>
                                @if ($checklist->created_at->isToday() || auth()->user()->hasRole('admin'))
                                    <div class="flex items-center gap-3 text-xs">
                                        <a href="{{ route('milestones.edit', $checklist) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                        <form method="POST" action="{{ route('milestones.destroy', $checklist) }}" onsubmit="return confirm('Delete this milestone?')">
                                            @csrf @method('DELETE')
                                            <button class="font-medium text-red-500 hover:text-red-700">Delete</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No milestones recorded yet." />
                @endforelse
            </x-card>

            {{ $checklists->links() }}
        </div>
    </div>
</x-app-layout>
