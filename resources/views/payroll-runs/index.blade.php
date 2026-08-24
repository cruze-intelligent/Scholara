<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payroll Runs') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('payroll-runs.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New payroll run
                </a>
            </div>

            <x-card>
                @forelse ($payrollRuns as $run)
                    <a href="{{ route('payroll-runs.show', $run) }}"
                       class="block border-b border-gray-100 py-3 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $run->period_start->format('d M') }} &ndash; {{ $run->period_end->format('d M Y') }}</p>
                                <p class="text-sm text-gray-500">{{ $run->payslips_count }} payslip(s)</p>
                            </div>
                            <x-badge :color="match($run->status) { 'paid' => 'green', 'approved' => 'blue', default => 'gray' }">
                                {{ ucfirst($run->status) }}
                            </x-badge>
                        </div>
                    </a>
                @empty
                    <x-empty-state message="No payroll runs yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
