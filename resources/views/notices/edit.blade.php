<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Notice') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('notices.update', $notice) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="title" label="Title" :value="$notice->title" />
                    <x-form.textarea name="body" label="Message" :value="$notice->body" />
                    <x-form.select name="audience" label="Audience" :selected="$notice->audience"
                        :options="['all' => 'Everyone', 'teacher' => 'Teachers', 'parent' => 'Parents', 'learner' => 'Learners']" />

                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
