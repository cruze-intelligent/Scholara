<x-guest-layout>
    <div class="text-center space-y-4">
        <x-application-logo class="w-16 h-16 mx-auto" />

        @if ($school->status === 'pending_review')
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Registration received') }}</h1>
            <p class="text-sm text-gray-600">
                {{ $school->name }} is registered under reference <strong>{{ $school->registration_number }}</strong>
                and is now waiting on review before you can sign in — this usually covers confirming the
                details you submitted. We'll be in touch by email once it's approved.
            </p>
        @elseif ($school->status === 'rejected')
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Registration not approved') }}</h1>
            <p class="text-sm text-gray-600">
                {{ $school->name }}'s registration ({{ $school->registration_number }}) wasn't approved.
                Contact support if you believe this is a mistake.
            </p>
        @elseif ($school->status === 'suspended')
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Account suspended') }}</h1>
            <p class="text-sm text-gray-600">
                {{ $school->name }}'s account is currently suspended. Contact support to resolve this.
            </p>
        @else
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Subscription needed') }}</h1>
            <p class="text-sm text-gray-600">
                {{ $school->name }}'s free trial has ended. A subscription is
                {{ number_format(\App\Models\SchoolSubscription::RATE_PER_STUDENT_UGX) }} UGX per student per
                90 days — contact support to arrange payment and reactivate access.
            </p>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="pt-2">
            @csrf
            <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Log out') }}</button>
        </form>
    </div>
</x-guest-layout>
