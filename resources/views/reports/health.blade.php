<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Health Trends') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card title="Clinic visits by reason (last 90 days)">
                @php $maxReason = $visitsByReason->max('count') ?: 1; @endphp
                @forelse ($visitsByReason as $row)
                    <div class="flex items-center gap-3 mb-1.5 last:mb-0">
                        <span class="text-sm text-gray-500 w-40 shrink-0 truncate">{{ $row->reason }}</span>
                        <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ round($row->count / $maxReason * 100) }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 w-8 text-right">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No clinic visits logged in the last 90 days.</p>
                @endforelse
            </x-card>

            <x-card title="Visit outcomes (last 90 days)">
                @forelse ($visitsByOutcome as $row)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ str_replace('_', ' ', ucfirst($row->outcome)) }}</span>
                        <span class="font-medium">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No clinic visits logged in the last 90 days.</p>
                @endforelse
            </x-card>

            <x-card title="Medications administered (last 90 days)">
                @forelse ($medicationsByName as $row)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ $row->medication_name }}</span>
                        <span class="font-medium">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No medication administrations logged in the last 90 days.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
