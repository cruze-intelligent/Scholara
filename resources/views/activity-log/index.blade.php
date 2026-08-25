<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My Activity') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500">A record of the actions you've taken on health and financial records.</p>

            <x-card>
                @forelse ($logs as $log)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center text-sm">
                        <div>
                            <x-badge :color="match($log->action) { 'create' => 'green', 'update' => 'blue', 'delete' => 'red', default => 'gray' }">
                                {{ ucfirst($log->action) }}
                            </x-badge>
                            <span class="ml-2 text-gray-700">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                        </div>
                        <span class="text-gray-400">{{ $log->created_at->format('d M Y, H:i') }}</span>
                    </div>
                @empty
                    <x-empty-state message="No recorded activity yet." />
                @endforelse
            </x-card>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
