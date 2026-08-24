<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit WOW Moment') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="text-sm text-gray-500 mb-4">{{ $moment->student->full_name }}</p>
                <form method="POST" action="{{ route('wow-moments.update', $moment) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.textarea name="caption" label="What happened" :value="$moment->caption" />
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
