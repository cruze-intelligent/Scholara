<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Issue a Book') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($books->isEmpty())
                    <p class="text-gray-500">No library books with available copies right now.</p>
                @else
                    <form method="POST" action="{{ route('book-loans.store') }}" class="space-y-4">
                        @csrf

                        <x-form.select name="inventory_item_id" label="Book"
                            :options="$books->mapWithKeys(fn ($b) => [$b->id => trim(\"{$b->name}\".($b->author ? \" — {$b->author}\" : '').\" ({$b->quantity} available)\")])" />

                        <x-form.select name="student_id" label="Student"
                            :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />

                        <x-form.input name="due_date" label="Due date (optional, default 14 days)" type="date" />

                        <x-primary-button>{{ __('Issue book') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
