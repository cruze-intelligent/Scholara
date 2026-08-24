<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bursar Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end gap-4">
                <a href="{{ route('invoices.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New invoice
                </a>
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    All invoices &rarr;
                </a>
            </div>

            <x-card>
                <p class="text-sm text-gray-500">Total unpaid</p>
                <p class="text-3xl font-semibold text-gray-800 mt-1">{{ number_format($unpaidTotal, 0) }} UGX</p>
            </x-card>

            <x-card title="Unpaid invoices">
                @forelse ($unpaidInvoices as $invoice)
                    <a href="{{ route('invoices.show', $invoice) }}"
                        class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm hover:text-indigo-600">
                        <span class="text-gray-700">{{ $invoice->student->full_name }} <span class="text-gray-400">— {{ $invoice->term }}</span></span>
                        <span class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">{{ number_format($invoice->amount_due, 0) }}</span>
                            <x-badge :color="$invoice->status === 'partially_paid' ? 'yellow' : 'red'">{{ str_replace('_', ' ', $invoice->status) }}</x-badge>
                        </span>
                    </a>
                @empty
                    <x-empty-state message="No outstanding invoices." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
