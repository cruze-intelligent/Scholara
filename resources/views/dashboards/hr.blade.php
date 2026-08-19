<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('HR Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Staff members</p>
                    <p class="text-3xl font-semibold">{{ $staffCount }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Latest payroll run</p>
                    @if ($latestPayrollRun)
                        <p class="text-lg font-semibold">
                            {{ $latestPayrollRun->period_start->format('d M') }} &ndash; {{ $latestPayrollRun->period_end->format('d M Y') }}
                        </p>
                        <p class="text-sm text-gray-500">{{ ucfirst($latestPayrollRun->status) }}</p>
                    @else
                        <p class="text-gray-500">No payroll runs yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
