@auth
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Help & FAQs') }}</h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                @include('faq._content')
            </div>
        </div>
    </x-app-layout>
@else
    <x-guest-layout>
        <div class="max-w-2xl">
            @include('faq._content')
        </div>
    </x-guest-layout>
@endauth
