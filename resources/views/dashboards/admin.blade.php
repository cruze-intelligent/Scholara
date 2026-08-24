<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end gap-4">
                <a href="{{ route('reports.academics') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Academic trends
                </a>
                <a href="{{ route('reports.health') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Health trends
                </a>
                <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Manage users &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-card>
                    <p class="text-sm text-gray-500">Students</p>
                    <p class="text-3xl font-semibold">{{ $studentCount }}</p>
                </x-card>
                <x-card>
                    <p class="text-sm text-gray-500">Staff</p>
                    <p class="text-3xl font-semibold">{{ $staffCount }}</p>
                </x-card>
                <x-card>
                    <p class="text-sm text-gray-500">Open incident reports</p>
                    <p class="text-3xl font-semibold">{{ $openIncidents }}</p>
                </x-card>
            </div>

            <x-card title="Recent notices">
                @forelse ($recentNotices as $notice)
                    <div class="border-b border-gray-100 py-2 last:border-0">
                        <p class="font-medium">{{ $notice->title }}</p>
                        <p class="text-sm text-gray-500">{{ $notice->published_at?->format('d M Y') ?? 'Not yet published' }}</p>
                    </div>
                @empty
                    <x-empty-state message="No notices yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
