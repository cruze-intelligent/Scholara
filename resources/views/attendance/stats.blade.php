<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Attendance Stats') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <form method="GET" action="{{ route('attendance.stats') }}" class="flex gap-4 items-end mb-6">
                    <x-form.select name="class_id" label="Class" class="w-56"
                        :options="$classes->mapWithKeys(fn ($c) => [$c->id => $c->name])" :selected="$classId" />
                    <x-secondary-button type="submit">{{ __('Load') }}</x-secondary-button>
                </form>

                @if ($stats)
                    <p class="text-xs text-gray-500 uppercase mb-2">% present by gender (all recorded dates)</p>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach (['female' => 'Girls', 'male' => 'Boys'] as $key => $label)
                            <div class="border rounded-lg p-4">
                                <p class="text-sm text-gray-500">{{ $label }}</p>
                                <p class="text-3xl font-semibold">
                                    {{ $stats[$key]['rate'] !== null ? $stats[$key]['rate'].'%' : '—' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $stats[$key]['present'] }} / {{ $stats[$key]['total'] }} records present
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if ($stats['female']['rate'] !== null && $stats['male']['rate'] !== null
                            && abs($stats['female']['rate'] - $stats['male']['rate']) >= 10)
                        <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            Attendance gap of {{ round(abs($stats['female']['rate'] - $stats['male']['rate']), 1) }} points
                            between girls and boys in this class.
                        </p>
                    @endif
                @else
                    <p class="text-gray-500">No class selected.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
