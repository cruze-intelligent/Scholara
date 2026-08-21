<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Guardian Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($students as $student)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ $student->full_name }}</h3>

                    <p class="text-sm text-gray-500 mb-2">Recent assessment scores</p>
                    @forelse ($student->assessmentScores->take(5) as $score)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                            <span>{{ $score->assessment->type }}</span>
                            <span>{{ $score->raw_score }} / {{ $score->assessment->max_score }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm mb-4">No scores recorded yet.</p>
                    @endforelse

                    @if ($student->subjectPredictions->isNotEmpty())
                        <p class="text-sm text-gray-500 mt-4 mb-2">Performance trend</p>
                        @foreach ($student->subjectPredictions as $prediction)
                            <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                                <span>{{ $prediction['subject']->name }}</span>
                                <span>{{ $prediction['predicted'] !== null ? round($prediction['predicted'], 1) : '—' }}</span>
                            </div>
                        @endforeach
                    @endif

                    <p class="text-sm text-gray-500 mt-4 mb-2">Invoices</p>
                    @forelse ($student->invoices as $invoice)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                            <span>{{ $invoice->term }}</span>
                            <span>{{ number_format($invoice->amount_due, 0) }} ({{ $invoice->status }})</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No invoices yet.</p>
                    @endforelse
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">No students linked to your account yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
