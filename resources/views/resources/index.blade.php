<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Teaching Resources') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (auth()->user()->hasAnyRole(['teacher', 'admin']))
                <div class="flex justify-end">
                    <a href="{{ route('resources.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        + Upload resource
                    </a>
                </div>
            @endif

            <x-card>
                @forelse ($resources as $resource)
                    <div class="flex justify-between items-start border-b border-gray-100 py-3 last:border-0">
                        <div>
                            <p class="font-medium">{{ $resource->title }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $resource->subject?->name ?? 'General' }} &middot; {{ $resource->schoolClass?->name }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $resource->teacher->name }} &middot; {{ $resource->created_at->format('d M Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('resources.download', $resource) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                Download
                            </a>
                            @if ($resource->teacher_id === auth()->id() || auth()->user()->hasRole('admin'))
                                <form method="POST" action="{{ route('resources.destroy', $resource) }}" onsubmit="return confirm('Delete this resource?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No teaching resources shared yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
