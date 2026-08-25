<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payment Status') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card
                x-data="{
                    status: '{{ $payment->status }}',
                    poll() {
                        if (this.status !== 'pending') return;
                        fetch('{{ route('invoices.pay.status-check', [$invoice, $payment]) }}')
                            .then(r => r.json())
                            .then(data => {
                                this.status = data.status;
                                if (this.status === 'pending') setTimeout(() => this.poll(), 5000);
                            });
                    },
                }"
                x-init="poll()"
            >
                <div x-show="status === 'pending'">
                    <p class="text-lg font-medium text-gray-800">
                        @if ($payment->method === 'mobile_money')
                            {{ __('Check your phone and approve the prompt') }}
                        @else
                            {{ __('Processing your payment…') }}
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('This page updates automatically.') }}</p>
                </div>

                <div x-show="status === 'completed'" class="text-green-600">
                    <p class="text-lg font-medium">{{ __('Payment received — thank you.') }}</p>
                    <a href="{{ route('invoices.payments.receipt', [$invoice, $payment]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('Download receipt') }}
                    </a>
                </div>

                <div x-show="status === 'failed'" class="text-red-600">
                    <p class="text-lg font-medium">{{ __('Payment failed.') }}</p>
                    <a href="{{ route('invoices.pay', $invoice) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('Try again') }}
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
