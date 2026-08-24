<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Medication Administration (eMAR)') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('medications.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Log administration
                </a>
            </div>

            <x-card>
                @forelse ($administrations as $administration)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">
                                    {{ $administration->student->full_name }} &mdash; {{ $administration->medication_name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $administration->dose }} via {{ $administration->route }}
                                    &middot; {{ $administration->administered_at->format('d M Y H:i') }}
                                    &middot; by {{ $administration->administeredBy->name }}
                                </p>
                            </div>
                            <x-badge :color="$administration->five_rights_checked ? 'green' : 'yellow'">
                                {{ $administration->five_rights_checked ? 'Five rights verified' : 'Incomplete checks' }}
                            </x-badge>
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No medication administrations logged yet." />
                @endforelse
            </x-card>

            {{ $administrations->links() }}
        </div>
    </div>
</x-app-layout>
