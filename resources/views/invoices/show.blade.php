<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $invoice->student->full_name }} &mdash; {{ $invoice->term }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-2xl font-semibold">{{ number_format($invoice->amount_due, 0) }} UGX</p>
                        <p class="text-sm text-gray-500">Due {{ $invoice->due_date->format('d M Y') }}</p>
                    </div>
                    <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partially_paid' => 'yellow', default => 'red' }">
                        {{ str_replace('_', ' ', $invoice->status) }}
                    </x-badge>
                </div>

                <p class="text-sm font-medium text-gray-500 mb-2">Payments</p>
                @forelse ($invoice->payments as $payment)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ ucfirst($payment->method) }} &middot; {{ $payment->paid_at?->format('d M Y H:i') ?? 'pending' }}</span>
                        <span class="flex items-center gap-2">
                            {{ number_format($payment->amount, 0) }} UGX
                            <x-badge :color="match($payment->status) { 'completed' => 'green', 'failed' => 'red', default => 'yellow' }">
                                {{ $payment->status }}
                            </x-badge>
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No payments recorded yet.</p>
                @endforelse
            </x-card>

            @if ($invoice->status !== 'paid')
                <x-card>
                    <p class="font-semibold text-gray-800 mb-4">Record a payment</p>
                    <form method="POST" action="{{ route('invoices.record-payment', $invoice) }}" class="space-y-4">
                        @csrf
                        <x-form.input name="amount" label="Amount (UGX)" type="number" />
                        <x-form.select name="method" label="Method" :options="['cash' => 'Cash', 'bank' => 'Bank']" />
                        <x-form.input name="reference" label="Reference (optional)" placeholder="Receipt/deposit slip no." />
                        <x-primary-button type="submit">{{ __('Record payment') }}</x-primary-button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
