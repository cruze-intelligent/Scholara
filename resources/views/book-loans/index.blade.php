<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Library Loans') }}</h2>
            <x-pin-toggle pin-key="book-loans.index" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (auth()->user()->hasAnyRole(['librarian', 'admin']))
                <div class="flex justify-end">
                    <a href="{{ route('book-loans.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        + Issue a book
                    </a>
                </div>
            @endif

            <x-card>
                @forelse ($loans as $loan)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-start">
                        <div>
                            <p class="font-medium">{{ $loan->inventoryItem->name }}</p>
                            <p class="text-sm text-gray-500">{{ $loan->student->full_name }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Borrowed {{ $loan->borrowed_at->format('d M Y') }} &middot; Due {{ $loan->due_date->format('d M Y') }}
                                @if ($loan->returned_at) &middot; Returned {{ $loan->returned_at->format('d M Y') }} @endif
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            @if ($loan->returned_at)
                                <x-badge :color="$loan->fine_amount > 0 ? 'yellow' : 'green'">
                                    {{ $loan->fine_amount > 0 ? 'Fine: '.number_format($loan->fine_amount, 0) : 'Returned' }}
                                </x-badge>
                            @elseif ($loan->isOverdue())
                                <x-badge color="red">Overdue</x-badge>
                            @else
                                <x-badge color="blue">On loan</x-badge>
                            @endif

                            @if (! $loan->returned_at && auth()->user()->hasAnyRole(['librarian', 'admin']))
                                <form method="POST" action="{{ route('book-loans.return', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Mark returned</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No library loans yet." />
                @endforelse
            </x-card>

            {{ $loans->links() }}
        </div>
    </div>
</x-app-layout>
