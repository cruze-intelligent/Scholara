<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Share a WOW Moment') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                @if ($students->isEmpty())
                    <p class="text-gray-500">No nursery-level students found.</p>
                @else
                    <form method="POST" action="{{ route('wow-moments.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <x-form.select name="student_id" label="Student"
                            :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" />
                        <x-form.textarea name="caption" label="What happened" />
                        <div>
                            <x-input-label for="photo" value="Photo (optional)" />
                            <input type="file" id="photo" name="photo" accept="image/*" class="mt-1 block w-full text-sm">
                            <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                        </div>
                        <x-primary-button>{{ __('Share') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
