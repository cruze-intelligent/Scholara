<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Noticeboard') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                @forelse ($notices as $notice)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <p class="font-medium">{{ $notice->title }}</p>
                        <p class="text-sm text-gray-500 mb-1">{{ $notice->published_at->format('d M Y') }}</p>
                        <p class="text-sm text-gray-700">{{ $notice->body }}</p>
                    </div>
                @empty
                    <x-empty-state message="No notices yet." />
                @endforelse
            </x-card>

            {{ $notices->links() }}
        </div>
    </div>
</x-app-layout>
