<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (session('importErrors'))
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 space-y-1">
                    @foreach (session('importErrors') as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('generatedPassword'))
                <div class="bg-amber-50 ring-1 ring-amber-200 rounded-xl p-4 text-sm">
                    <p class="font-medium text-amber-800">
                        Temporary password for {{ session('generatedPasswordFor') }}:
                        <code class="bg-white px-2 py-0.5 rounded border border-amber-300">{{ session('generatedPassword') }}</code>
                    </p>
                    <p class="text-amber-700 mt-1">
                        Share this with them directly — it won't be shown again. They can change it
                        from their profile after logging in.
                    </p>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-500">{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }}</h3>
                <div class="flex items-center gap-4">
                    <a href="{{ route('students.export') }}" class="text-xs font-medium text-gray-500 hover:text-gray-700">Export CSV</a>
                    <a href="{{ route('students.import') }}" class="text-xs font-medium text-gray-500 hover:text-gray-700">Import CSV</a>
                    <a href="{{ route('users.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-colors">
                        + Add user
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('users.index') }}"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors {{ ! $roleFilter ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    All ({{ $roleCounts->sum() }})
                </a>
                @foreach ($roleCounts as $role => $count)
                    <a href="{{ route('users.index', ['role' => $role]) }}"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors {{ $roleFilter === $role ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ ucfirst($role) }} ({{ $count }})
                    </a>
                @endforeach
            </div>

            <x-card>
                @forelse ($users as $user)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-center gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                {{ collect(explode(' ', $user->name))->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium flex items-center gap-2 truncate">
                                    {{ $user->name }}
                                    @if (! $user->is_active)
                                        <x-badge color="red">Deactivated</x-badge>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ $user->email ?? $user->phone }} &middot; {{ $user->roles->pluck('name')->map('ucfirst')->join(', ') ?: 'No role assigned' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm shrink-0">
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
                    <x-empty-state message="No users match this filter." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
