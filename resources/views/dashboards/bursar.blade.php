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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Total unpaid</p>
                <p class="text-3xl font-semibold">{{ number_format($unpaidTotal, 0) }}</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Unpaid invoices</h3>
                @forelse ($unpaidInvoices as $invoice)
                    <a href="{{ route('invoices.show', $invoice) }}"
                        class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm hover:text-indigo-600">
                        <span>{{ $invoice->student->full_name }} &mdash; {{ $invoice->term }}</span>
                        <span>{{ number_format($invoice->amount_due, 0) }} ({{ $invoice->status }})</span>
                    </a>
                @empty
                    <p class="text-gray-500">No outstanding invoices.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
