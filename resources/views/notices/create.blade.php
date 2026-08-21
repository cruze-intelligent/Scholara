<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Notice') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('notices.store') }}" class="space-y-4">
                    @csrf
                    <x-form.input name="title" label="Title" />
                    <x-form.textarea name="body" label="Message" />
                    <x-form.select name="audience" label="Audience"
                        :options="['all' => 'Everyone', 'teacher' => 'Teachers', 'parent' => 'Parents', 'learner' => 'Learners']" />
                    <x-form.checkbox name="publish" label="Publish immediately" />

                    <x-primary-button>{{ __('Save notice') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
