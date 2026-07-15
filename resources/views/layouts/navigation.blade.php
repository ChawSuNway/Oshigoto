{{-- Dark overlay behind the drawer on mobile --}}
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" x-transition.opacity
     class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

{{-- Sidebar (full on open, icon-only rail when collapsed on large screens) --}}
{{-- The static -translate-x-full / lg:translate-x-0 / lg:w-64 match the initial Alpine
     state (closed on mobile, open on >=lg) so the drawer does not flash on load. --}}
<aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-white shadow-md-2 overflow-x-hidden transition-all duration-200 ease-in-out lg:z-40 -translate-x-full lg:translate-x-0 lg:w-64"
       :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen, 'lg:w-64': sidebarOpen, 'lg:w-16': ! sidebarOpen }">

    {{-- Brand --}}
    <div class="flex items-center gap-2 h-16 px-4 border-b border-gray-100 shrink-0" :class="{ 'lg:justify-center lg:px-2': ! sidebarOpen }">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
            <x-application-logo class="h-9 w-auto max-w-full shrink-0" />
            <span x-show="sidebarOpen" class="font-bold tracking-tight text-gray-800 truncate whitespace-nowrap">{{ config('app.name', 'Laravel') }}</span>
        </a>
        <button type="button" @click="sidebarOpen = false"
                class="ml-auto lg:hidden rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Navigation links --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-1" @click="if (window.innerWidth < 1024) sidebarOpen = false">
        <x-sidebar-link :href="route('dashboard')" title="Dashboard" :active="request()->routeIs('dashboard')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Dashboard') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('reports.index')" title="Reports" :active="request()->routeIs('reports.index', 'reports.show', 'reports.edit', 'reports.store', 'reports.update')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Report List') }}</span>
        </x-sidebar-link>

        @unless (auth()->user()->isManager())
            <x-sidebar-link :href="route('reports.create')" title="New Daily Report" :active="request()->routeIs('reports.create')">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('New Report') }}</span>
            </x-sidebar-link>
        @endunless

        <x-sidebar-link :href="route('reports.monthly')" title="New Monthly Report" :active="request()->routeIs('reports.monthly')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Monthly Report') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('late.index')" title="Late Coming" :active="request()->routeIs('late.*')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Late Coming') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('early.index')" title="Early Leave" :active="request()->routeIs('early.*')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75 12 6m0 0 3.75 3.75M12 6v12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Early Leave') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('half.index')" title="Half Day Leave" :active="request()->routeIs('half.*')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Half Day Leave') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('leave.index')" title="Leave" :active="request()->routeIs('leave.*')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Leave') }}</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('departments.index')" title="Departments" :active="request()->routeIs('departments.*')">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Departments') }}</span>
        </x-sidebar-link>

        @if (auth()->user()->isAdmin())
            <div class="pt-4 mt-2 border-t border-gray-100">
                <p x-show="sidebarOpen" class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Admin</p>
                <x-sidebar-link :href="route('system-ids.index')" title="System IDs" :active="request()->routeIs('system-ids.*')">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" /></svg>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('System IDs') }}</span>
                </x-sidebar-link>
                <x-sidebar-link :href="route('users.index')" title="Users" :active="request()->routeIs('users.*')">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Users') }}</span>
                </x-sidebar-link>
            </div>
        @endif
    </nav>

    {{-- User section --}}
    <div class="border-t border-gray-100 p-3 shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Log Out"
                    class="md-ripple w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                    :class="{ 'lg:justify-center': ! sidebarOpen }">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
</aside>
