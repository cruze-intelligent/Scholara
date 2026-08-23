<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Scholara') }} — School management, all in one place</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 text-gray-800 antialiased">
        <header class="border-b border-gray-100 bg-white">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-8" />
                    <span class="font-semibold text-xl tracking-tight text-gray-900">Scholara</span>
                </span>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Go to dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-block px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        Log in
                    </a>
                @endauth
            </div>
        </header>

        <main>
            <section class="max-w-6xl mx-auto px-6 pt-16 pb-12 text-center">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900 max-w-2xl mx-auto">
                    One system for your whole school — learners, parents, teachers, and staff.
                </h1>
                <p class="mt-4 text-lg text-gray-500 max-w-xl mx-auto">
                    Scholara replaces the spreadsheets, paper registers, and separate apps with a
                    single source of truth — each role sees exactly what's theirs to manage, and
                    nothing they don't need.
                </p>
                @guest
                    <a href="{{ route('login') }}" class="inline-block mt-8 px-6 py-3 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                        Log in to your account
                    </a>
                    <p class="mt-3 text-sm text-gray-400">
                        Accounts are set up by your school administrator.
                    </p>
                @endguest
            </section>

            <section class="max-w-6xl mx-auto px-6 pb-20">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ([
                        ['Admin', 'Runs the whole school: creates and manages every account, oversees discipline and strategy, sees every module.'],
                        ['Teacher', 'Marksheets, lesson plans, attendance, notices, and health-alert logging for their own classes.'],
                        ['Parent', 'One account for every child they have at the school — academics, fees, attendance, and health, all in one view.'],
                        ['Learner', 'Their own scores, the noticeboard, and a way to report an issue.'],
                    ] as [$role, $description])
                        <div class="bg-white border border-gray-100 rounded-lg p-5">
                            <p class="font-semibold text-gray-900">{{ $role }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="text-sm text-gray-400 mt-6">
                    Plus dedicated tools for nurses, HR, bursars, and librarians once your school
                    administrator sets up your account.
                </p>
            </section>
        </main>

        <footer class="border-t border-gray-100 py-6">
            <p class="text-center text-sm text-gray-400">
                Scholara — built by Cruze Intelligent Systems
            </p>
        </footer>
    </body>
</html>
