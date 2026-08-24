<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card title="My classes">
                @forelse ($classes as $class)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center">
                        <span class="font-medium text-gray-800">{{ $class->name }} <span class="font-normal text-gray-400">({{ $class->level }})</span></span>
                        <span class="text-sm text-gray-500">{{ $class->students_count }} students</span>
                    </div>
                @empty
                    <x-empty-state message="No classes assigned yet." />
                @endforelse
            </x-card>

            <x-card title="Upcoming lesson plans">
                @forelse ($upcomingLessonPlans as $plan)
                    <div class="border-b border-gray-100 py-2.5 last:border-0">
                        <p class="font-medium text-gray-800">{{ $plan->date->format('d M Y') }} &mdash; {{ $plan->schoolClass->name }}</p>
                    </div>
                @empty
                    <x-empty-state message="No lesson plans scheduled." />
                @endforelse
            </x-card>

            <x-card title="Support Strategy alerts">
                @forelse ($supportAlerts as $alert)
                    <div class="border-b border-gray-100 py-2.5 last:border-0 flex justify-between items-center text-sm">
                        <span class="text-gray-700">{{ $alert['student']->full_name }} &mdash; {{ $alert['subject']->name }}</span>
                        <x-badge color="yellow">predicted {{ $alert['predicted'] }} (baseline {{ $alert['baseline'] }})</x-badge>
                    </div>
                @empty
                    <x-empty-state message="No students currently flagged." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
