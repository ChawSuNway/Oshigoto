<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <style>[x-cloak]{display:none !important;}</style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=roboto:300,400,500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div x-data="{ sidebarOpen: window.innerWidth >= 1024 }" @keydown.escape.window="sidebarOpen = false"
             class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">

            {{-- Left sidebar (+ mobile drawer) --}}
            @include('layouts.navigation')

            {{-- Content column, offset by the sidebar (full width when open, rail when collapsed) --}}
            <div class="transition-[padding] duration-200 ease-in-out" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-16'">
                @php
                    $draftCount = (! auth()->user()->isManager())
                        ? auth()->user()->reports()->whereNull('sent_at')->count()
                        : 0;
                @endphp

                {{-- Top app bar --}}
                <header class="sticky top-0 z-30 bg-white shadow-md-1">
                    <div class="flex items-center gap-3 h-16 px-4 sm:px-6">
                        <button type="button" @click="sidebarOpen = ! sidebarOpen"
                                class="md-ripple rounded-full p-2 -ml-2 text-gray-600 hover:bg-gray-100" aria-label="Toggle menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                        </button>

                        <div class="ml-auto flex items-center gap-1 sm:gap-2">
                            <div class="flex items-center gap-2 pr-1 sm:pr-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-semibold shrink-0">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="hidden md:block text-sm font-medium text-gray-700 max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="relative">
                                <a href="{{ route('reports.index') }}" title="Draft reports"
                                   class="md-ripple block rounded-full p-2 text-gray-500 hover:bg-gray-100">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                </a>
                                @if ($draftCount > 0)
                                    <span class="pointer-events-none absolute top-0 right-0 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white ring-2 ring-white">{{ $draftCount }}</span>
                                @endif
                            </div>
                            <a href="{{ route('profile.edit') }}" title="Settings"
                               class="md-ripple rounded-full p-2 text-gray-500 hover:bg-gray-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-gray-200">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
