<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Fee Structures') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <p class="text-sm text-gray-500">
                The standard fee per level per term — generate an invoice for every student in
                that level from one of these instead of typing the same amount in by hand.
            </p>

            @hasrole('admin')
                <div class="text-right">
                    <a href="{{ route('fee-structures.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-colors">
                        + Add fee structure
                    </a>
                </div>
            @endhasrole

            <x-card>
                @forelse ($feeStructures as $fee)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center gap-3">
                        <div>
                            <p class="font-medium text-gray-800">
                                {{ ucwords(str_replace('_', ' ', $fee->curriculum_level)) }} &middot; {{ $fee->term }}
                            </p>
                            <p class="text-sm text-gray-500">{{ $fee->label }} &middot; {{ number_format($fee->amount) }} UGX &middot; due {{ $fee->due_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-sm shrink-0">
                            <form method="POST" action="{{ route('fee-structures.generate', $fee) }}" onsubmit="return confirm('Generate an invoice for every {{ $fee->curriculum_level }} student who doesn\'t already have one for {{ $fee->term }}?');">
                                @csrf
                                <button type="submit" class="font-medium text-indigo-600 hover:text-indigo-800">Generate invoices</button>
                            </form>
                            @hasrole('admin')
                                <form method="POST" action="{{ route('fee-structures.destroy', $fee) }}" onsubmit="return confirm('Remove this fee structure?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-medium text-red-500 hover:text-red-700">Remove</button>
                                </form>
                            @endhasrole
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No fee structures set up yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
