<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('WOW Moments') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('wow-moments.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Share a moment
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($moments as $moment)
                    <x-card>
                        @if ($moment->media_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($moment->media_path) }}" class="rounded-md mb-3 w-full object-cover max-h-48">
                        @endif
                        <p class="font-medium">{{ $moment->student->full_name }}</p>
                        <p class="text-sm text-gray-500">{{ $moment->caption }}</p>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-400">by {{ $moment->teacher->name }}</p>
                            @if (($moment->teacher_id === auth()->id() && $moment->created_at->isToday()) || auth()->user()->hasRole('admin'))
                                <div class="flex items-center gap-3 text-xs">
                                    <a href="{{ route('wow-moments.edit', $moment) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                    <form method="POST" action="{{ route('wow-moments.destroy', $moment) }}" onsubmit="return confirm('Delete this moment?')">
                                        @csrf @method('DELETE')
                                        <button class="font-medium text-red-500 hover:text-red-700">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </x-card>
                @empty
                    <x-empty-state message="No WOW moments shared yet." class="sm:col-span-2" />
                @endforelse
            </div>

            {{ $moments->links() }}
        </div>
    </div>
</x-app-layout>
