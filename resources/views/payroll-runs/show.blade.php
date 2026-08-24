<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payroll Run') }} &mdash; {{ $payrollRun->period_start->format('d M') }} to {{ $payrollRun->period_end->format('d M Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <div class="flex justify-between items-center mb-4">
                    <x-badge :color="match($payrollRun->status) { 'paid' => 'green', 'approved' => 'blue', default => 'gray' }">
                        {{ ucfirst($payrollRun->status) }}
                    </x-badge>

                    @if ($payrollRun->status === 'draft')
                        <div class="flex items-center gap-3">
                            <a href="{{ route('payroll-runs.edit', $payrollRun) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                {{ __('Edit') }}
                            </a>
                            <form method="POST" action="{{ route('payroll-runs.destroy', $payrollRun) }}" onsubmit="return confirm('Delete this payroll run?')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-medium text-red-500 hover:text-red-700">{{ __('Delete') }}</button>
                            </form>
                            <form method="POST" action="{{ route('payroll-runs.generate', $payrollRun) }}">
                                @csrf
                                <x-primary-button>{{ __('Generate payslips') }}</x-primary-button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-4 gap-2 text-xs font-semibold text-gray-500 uppercase border-b pb-2">
                    <span>Staff</span><span>Gross</span><span>PAYE</span><span>NSSF</span>
                </div>
                @forelse ($payrollRun->payslips as $payslip)
                    <div class="grid grid-cols-4 gap-2 py-2 border-b border-gray-100 last:border-0 text-sm">
                        <span>{{ $payslip->staffProfile->user->name }}</span>
                        <span>{{ number_format($payslip->gross_pay, 0) }}</span>
                        <span>{{ number_format($payslip->paye, 0) }}</span>
                        <span>{{ number_format($payslip->nssf, 0) }} &middot; net {{ number_format($payslip->net_pay, 0) }}</span>
                    </div>
                @empty
                    <x-empty-state message="No payslips generated yet — staff need a monthly gross salary set first." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
