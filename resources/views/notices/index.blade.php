<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Noticeboard') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('notices.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New notice
                </a>
            </div>

            <x-card>
                @forelse ($notices as $notice)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">{{ $notice->title }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ $notice->body }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $notice->author->name }} &middot; audience: {{ $notice->audience }}
                                </p>
                            </div>
                            @if ($notice->published_at)
                                <x-badge color="green">Published</x-badge>
                            @else
                                <form method="POST" action="{{ route('notices.publish', $notice) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Publish</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No notices yet.</p>
                @endforelse
            </x-card>

            {{ $notices->links() }}
        </div>
    </div>
</x-app-layout>
