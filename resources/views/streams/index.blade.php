<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Streams') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <p class="text-sm text-gray-500">
                A quick identification label for a student or teacher — e.g. "Blue" or "Green" —
                separate from their actual class.
            </p>

            <x-card>
                <form method="POST" action="{{ route('streams.store') }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-form.input name="name" label="New stream name" placeholder="e.g. Blue" />
                    </div>
                    <x-primary-button>{{ __('Add') }}</x-primary-button>
                </form>
            </x-card>

            <x-card>
                @forelse ($streams as $stream)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-800">{{ $stream->name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $stream->students_count }} student{{ $stream->students_count === 1 ? '' : 's' }}
                                &middot; {{ $stream->staff_profiles_count }} staff
                            </p>
                        </div>
                        <form method="POST" action="{{ route('streams.destroy', $stream) }}" onsubmit="return confirm('Remove this stream? Students/staff attached to it will be unassigned, not deleted.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Remove</button>
                        </form>
                    </div>
                @empty
                    <x-empty-state message="No streams yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
