@unless (auth()->check())
    <h1 class="text-xl font-semibold text-gray-800 text-center mb-6">{{ __('Help & FAQs') }}</h1>
@endunless

<div x-data="{ role: '{{ auth()->user()?->roles->first()?->name === 'super_admin' ? 'Super Admin' : (auth()->user()?->roles->first()?->name ? ucfirst(auth()->user()->roles->first()->name) : 'General') }}' }">
    <div class="flex flex-wrap gap-2">
        @foreach ($sections as $role => $items)
            <button type="button" @click="role = '{{ $role }}'"
                :class="role === '{{ $role }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors">
                {{ $role }}
            </button>
        @endforeach
    </div>

    @foreach ($sections as $role => $items)
        <div x-show="role === '{{ $role }}'" x-cloak class="mt-4">
            <x-card>
                @foreach ($items as $item)
                    <details class="group border-b border-gray-100 last:border-0 py-2">
                        <summary class="flex items-center justify-between cursor-pointer list-none font-medium text-gray-800 text-sm">
                            {{ $item['q'] }}
                            <span class="text-gray-400 group-open:rotate-180 transition-transform">⌄</span>
                        </summary>
                        <p class="text-sm text-gray-500 mt-2 pr-6">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </x-card>
        </div>
    @endforeach
</div>
