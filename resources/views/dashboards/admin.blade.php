<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Manage users &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Students</p>
                    <p class="text-3xl font-semibold">{{ $studentCount }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Staff</p>
                    <p class="text-3xl font-semibold">{{ $staffCount }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Open incident reports</p>
                    <p class="text-3xl font-semibold">{{ $openIncidents }}</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Recent notices</h3>
                @forelse ($recentNotices as $notice)
                    <div class="border-b border-gray-100 py-2 last:border-0">
                        <p class="font-medium">{{ $notice->title }}</p>
                        <p class="text-sm text-gray-500">{{ $notice->published_at?->format('d M Y') ?? 'Not yet published' }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No notices yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
