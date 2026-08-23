<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pay Invoice') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <p class="text-sm text-gray-500 mb-1">{{ $invoice->term }}</p>
                <p class="text-2xl font-semibold text-gray-800 mb-6">
                    {{ number_format($invoice->amount_due, 0) }} UGX
                </p>

                @if ($errors->any())
                    <p class="text-sm font-medium text-red-600 mb-4">{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('invoices.pay.store', $invoice) }}" x-data="{ method: 'mobile_money' }" class="space-y-4">
                    @csrf

                    <div class="flex gap-3">
                        <label class="flex-1 border rounded-lg p-4 cursor-pointer" :class="method === 'mobile_money' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'">
                            <input type="radio" name="method" value="mobile_money" x-model="method" class="sr-only">
                            <span class="block font-medium text-gray-800">{{ __('Mobile Money') }}</span>
                            <span class="block text-sm text-gray-500">MTN, Airtel</span>
                        </label>
                        <label class="flex-1 border rounded-lg p-4 cursor-pointer" :class="method === 'card' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'">
                            <input type="radio" name="method" value="card" x-model="method" class="sr-only">
                            <span class="block font-medium text-gray-800">{{ __('Card') }}</span>
                            <span class="block text-sm text-gray-500">Visa, Mastercard</span>
                        </label>
                    </div>

                    <div x-show="method === 'mobile_money'">
                        <x-form.input name="phone_number" label="Phone number" placeholder="07XXXXXXXX" />
                    </div>

                    <x-primary-button type="submit">{{ __('Pay now') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
