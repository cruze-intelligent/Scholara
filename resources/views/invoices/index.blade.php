<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Invoices') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('invoices.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New invoice
                </a>
            </div>

            <x-card>
                @forelse ($invoices as $invoice)
                    <a href="{{ route('invoices.show', $invoice) }}"
                        class="block border-b border-gray-100 py-3 last:border-0 flex justify-between items-center hover:bg-gray-50 -mx-6 px-6">
                        <div>
                            <p class="font-medium">{{ $invoice->student->full_name }} &mdash; {{ $invoice->term }}</p>
                            <p class="text-sm text-gray-500">Due {{ $invoice->due_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">{{ number_format($invoice->amount_due, 0) }} UGX</p>
                            <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partially_paid' => 'yellow', default => 'red' }">
                                {{ str_replace('_', ' ', $invoice->status) }}
                            </x-badge>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500">No invoices yet.</p>
                @endforelse
            </x-card>

            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
