<x-guest-layout>
    <p class="text-sm text-gray-600 mb-4">
        {{ __('Register your school — a free 30-day trial, no card required. Your submission is reviewed before your account is activated.') }}
    </p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('School') }}</p>
            <div class="space-y-3">
                <x-form.input name="school_name" label="School name" />
                <x-form.input name="school_address" label="Address" />
                <x-form.input name="subdomain" label="Subdomain" placeholder="e.g. greenhill" />
                <x-form.input name="moe_registration_number" label="Ministry of Education registration number (optional)" />
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('School admin (you)') }}</p>
            <div class="space-y-3">
                <x-form.input name="admin_name" label="Full name" />
                <x-form.input name="admin_email" label="Email" type="email" />
                <x-form.input name="admin_phone" label="Phone (optional)" />
                <x-form.input name="password" label="Password" type="password" />
                <x-form.input name="password_confirmation" label="Confirm password" type="password" />
            </div>
        </div>

        <div class="flex items-center justify-end pt-2">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register school') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
