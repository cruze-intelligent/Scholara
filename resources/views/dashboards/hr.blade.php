<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('HR Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('dashboards._pinned')
            <div class="flex justify-end">
                <a href="{{ route('payroll-runs.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Payroll runs &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-card>
                    <p class="text-sm text-gray-500">Staff members</p>
                    <p class="text-3xl font-semibold text-gray-800 mt-1">{{ $staffCount }}</p>
                </x-card>
                <x-card>
                    <p class="text-sm text-gray-500">Latest payroll run</p>
                    @if ($latestPayrollRun)
                        <p class="text-lg font-semibold text-gray-800 mt-1">
                            {{ $latestPayrollRun->period_start->format('d M') }} &ndash; {{ $latestPayrollRun->period_end->format('d M Y') }}
                        </p>
                        <x-badge :color="$latestPayrollRun->status === 'approved' ? 'green' : 'yellow'" class="mt-1">
                            {{ ucfirst($latestPayrollRun->status) }}
                        </x-badge>
                    @else
                        <p class="text-gray-400 text-sm mt-1">No payroll runs yet.</p>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
