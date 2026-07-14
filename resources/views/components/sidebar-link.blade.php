@props(['active' => false])

@php
    $classes = $active
        ? 'md-ripple flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium bg-gray-800 text-white shadow-md-1'
        : 'md-ripple flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} :class="{ 'lg:justify-center': ! sidebarOpen }">
    {{ $slot }}
</a>
