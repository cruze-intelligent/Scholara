@props(['name'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
    @switch($name)
        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5M5 9.5V20h5v-6h4v6h5V9.5" />
            @break
        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M20 19v-1.5a3 3 0 0 0-2.2-2.9M14 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm5.5 1a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
            @break
        @case('identification')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 6.5h17a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-17a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1Z" />
            <circle cx="8.5" cy="12" r="2" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 16.2c.4-1.2 1.4-2 2.5-2s2.1.8 2.5 2M14 10h5M14 13.5h5" />
            @break
        @case('cog')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 13.5a1.7 1.7 0 0 0 .35 1.9l.06.06a2 2 0 1 1-2.9 2.9l-.06-.06a1.7 1.7 0 0 0-1.9-.35 1.7 1.7 0 0 0-1 1.6V20a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.9.35l-.06.06a2 2 0 1 1-2.9-2.9l.06-.06a1.7 1.7 0 0 0 .35-1.9 1.7 1.7 0 0 0-1.6-1H4a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.35-1.9l-.06-.06a2 2 0 1 1 2.9-2.9l.06.06a1.7 1.7 0 0 0 1.9.35H10a1.7 1.7 0 0 0 1-1.6V4a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.35l.06-.06a2 2 0 1 1 2.9 2.9l-.06.06a1.7 1.7 0 0 0-.35 1.9V10c.16.7.72 1.26 1.4 1.4H20a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1.1Z" />
            @break
        @case('clipboard-check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5h6a1 1 0 0 1 1 1V6h-8v-.5a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2M9.5 13l2 2 3.5-4" />
            @break
        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9.5h15M7 4v3M17 4v3M5.5 6.5h13a1 1 0 0 1 1 1V19a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1Z" />
            @break
        @case('megaphone')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 9.5v5h2.4L13 18V6l-7.1 3.5H3.5Zm14-2.2a5 5 0 0 1 0 9.4M17 10a2 2 0 0 1 0 4" />
            @break
        @case('chart-bar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 19.5v-7M11 19.5v-11M17 19.5v-4.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19.5h17" />
            @break
        @case('book-open')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5c-1.4-1-3.6-1.5-6-1.5-.6 0-1 .4-1 1v11c0 .6.4 1 1 1 2.4 0 4.6.5 6 1.5m0-13c1.4-1 3.6-1.5 6-1.5.6 0 1 .4 1 1v11c0 .6-.4 1-1 1-2.4 0-4.6.5-6 1.5m0-13v13" />
            @break
        @case('heart-pulse')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19s-7-4.4-9-8.8C1.6 6.8 3.4 4 6.4 4c1.7 0 3 .9 3.6 2.1M12 19s7-4.4 9-8.8C22.4 6.8 20.6 4 17.6 4c-1.7 0-3 .9-3.6 2.1" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 11h3l1.5-3 2 5 1.5-3h9.5" />
            @break
        @case('plus-circle')
            <circle cx="12" cy="12" r="8.5" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.5v7M8.5 12h7" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2" />
            @break
        @case('exclamation-triangle')
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.7 4.9 2.9 18a1.5 1.5 0 0 0 1.3 2.3h15.6a1.5 1.5 0 0 0 1.3-2.3L13.3 4.9a1.5 1.5 0 0 0-2.6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v4M12 16.8v.05" />
            @break
        @case('door')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.5V4a1 1 0 0 1 1-1h9.5v17.5M6 20.5h12M9.5 20.5V3M15 11.2v1.6" />
            @break
        @case('banknotes')
            <rect x="2.5" y="6.5" width="19" height="11" rx="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="12" cy="12" r="2.5" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('credit-card')
            <rect x="2.5" y="5.5" width="19" height="13" rx="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 10h19" />
            @break
        @case('archive-box')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 7.5h17v3h-17v-3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5V19a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-8.5M10 14h4" />
            @break
        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.5 10.2 7 13.5 8.2 10.2 9.5 9 13l-1.2-3.5L4.5 8.2 7.8 7 9 3.5ZM17.5 12.5l.8 2.3 2.3.8-2.3.8-.8 2.3-.8-2.3-2.3-.8 2.3-.8.8-2.3Z" />
            @break
        @case('user')
            <circle cx="12" cy="8.5" r="3.5" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20a7.5 7.5 0 0 1 15 0" />
            @break
        @case('logout')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20H5.5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1H9M16.5 16l4-4-4-4M20 12H9" />
            @break
        @default
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
