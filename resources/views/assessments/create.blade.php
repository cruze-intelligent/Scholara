<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Assessment') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                @if ($assignments->isEmpty())
                    <p class="text-gray-500">
                        You have no subject/class assignments yet — ask an admin to assign you a subject to teach
                        before creating an assessment.
                    </p>
                @else
                    <form method="POST" action="{{ route('assessments.store') }}" class="space-y-4">
                        @csrf

                        <x-form.select name="assignment_id" label="Subject / Class"
                            :options="$assignments->mapWithKeys(fn ($a) => [$a->id => \"{$a->subject->name} — {$a->schoolClass->name}\"])" />

                        <x-form.select name="type" label="Type" :options="['AoI' => 'Assessment of Learning (AoI)', 'MOT' => 'Mid of Term (MOT)', 'EOT' => 'End of Term (EOT)']" />

                        <x-form.input name="term" label="Term" placeholder="e.g. Term 2 2026" />
                        <x-form.input name="max_score" label="Max score" type="number" value="100" />
                        <x-form.input name="weight" label="Weight (optional, default 1)" type="number" />

                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
