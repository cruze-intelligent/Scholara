<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (session('generatedPassword'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm">
                    <p class="font-medium text-yellow-800">
                        Temporary password for {{ session('generatedPasswordFor') }}:
                        <code class="bg-white px-2 py-0.5 rounded border border-yellow-300">{{ session('generatedPassword') }}</code>
                    </p>
                    <p class="text-yellow-700 mt-1">
                        Share this with them directly — it won't be shown again. They can change it
                        from their profile after logging in.
                    </p>
                </div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('users.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Add user
                </a>
            </div>

            <x-card>
                @forelse ($users as $user)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center">
                        <div>
                            <p class="font-medium flex items-center gap-2">
                                {{ $user->name }}
                                <x-badge :color="$user->is_active ? 'green' : 'red'">
                                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                                </x-badge>
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $user->email }} &middot; {{ $user->roles->pluck('name')->map('ucfirst')->join(', ') ?: 'No role assigned' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('users.edit', $user) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="font-medium {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No users yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
