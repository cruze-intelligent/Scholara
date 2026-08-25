{{-- Deliberately doesn't @include('dashboards._pinned') — that widget's calendar query is
     school-scoped, and a super_admin has no single school for it to make sense against. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Platform Dashboard') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-stat-tile label="Schools" :value="$totalSchools" icon="identification" tone="indigo" />
                <x-stat-tile label="Students (platform-wide)" :value="$totalStudents" icon="users" tone="indigo" />
                <x-stat-tile label="Pending review" :value="$schoolCounts['pending_review'] ?? 0" icon="exclamation-triangle" tone="amber" />
                <x-stat-tile label="Revenue this term (UGX)" :value="number_format($revenueThisTerm)" icon="banknotes" tone="green" />
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\School::STATUSES as $status)
                    <x-badge :color="match($status) { 'active', 'trial' => 'green', 'pending_review' => 'yellow', 'suspended', 'rejected' => 'red', default => 'gray' }">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}: {{ $schoolCounts[$status] ?? 0 }}
                    </x-badge>
                @endforeach
            </div>

            <div class="flex gap-4">
                <a href="{{ route('super-admin.schools') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage schools &rarr;</a>
                <a href="{{ route('super-admin.activity') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Platform activity log &rarr;</a>
            </div>

            <x-card title="Awaiting review">
                @forelse ($pendingSchools as $school)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ $school->name }}</p>
                            <p class="text-gray-500">{{ $school->registration_number }} &middot; registered {{ $school->created_at->diffForHumans() }}</p>
                        </div>
                        <a href="{{ route('super-admin.schools') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Review &rarr;</a>
                    </div>
                @empty
                    <x-empty-state message="Nothing waiting on review." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
