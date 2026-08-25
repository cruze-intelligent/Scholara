<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Schools') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                @foreach ($schools as $school)
                    @php $latestSub = $school->subscriptions->first(); @endphp
                    <div class="border-b border-gray-100 py-4 last:border-0">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ $school->name }}
                                    <x-badge :color="match($school->status) { 'active', 'trial' => 'green', 'pending_review' => 'yellow', 'suspended', 'rejected' => 'red', default => 'gray' }">
                                        {{ ucfirst(str_replace('_', ' ', $school->status)) }}
                                    </x-badge>
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $school->registration_number }} &middot; {{ $school->students_count }} students
                                    @if ($school->status === 'trial' && $school->trial_ends_at)
                                        &middot; trial ends {{ $school->trial_ends_at->format('d M Y') }}
                                    @endif
                                    @if ($latestSub)
                                        &middot; last billed {{ $latestSub->period_end->format('d M Y') }} ({{ $latestSub->status }})
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-3 text-sm shrink-0">
                                @if ($school->status === 'pending_review')
                                    <form method="POST" action="{{ route('super-admin.schools.approve', $school) }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-green-600 hover:text-green-800">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.schools.reject', $school) }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-800">Reject</button>
                                    </form>
                                @elseif ($school->status === 'suspended')
                                    <form method="POST" action="{{ route('super-admin.schools.reactivate', $school) }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-green-600 hover:text-green-800">Reactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('super-admin.schools.suspend', $school) }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-800">Suspend</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if (! in_array($school->status, ['pending_review', 'rejected']))
                            <div class="mt-2 flex items-center gap-3">
                                <form method="POST" action="{{ route('super-admin.schools.subscriptions.generate', $school) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Generate next billing period</button>
                                </form>
                                @if ($latestSub && $latestSub->status === 'pending')
                                    <form method="POST" action="{{ route('super-admin.subscriptions.mark-paid', $latestSub) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-green-600 hover:text-green-800">Mark {{ number_format($latestSub->amount) }} UGX as paid</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </x-card>
        </div>
    </div>
</x-app-layout>
