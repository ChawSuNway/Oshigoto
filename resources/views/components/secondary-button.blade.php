<button {{ $attributes->merge(['type' => 'button', 'class' => 'md-ripple inline-flex items-center justify-center gap-2 px-4 py-2 bg-transparent border border-indigo-600 rounded font-medium text-sm text-indigo-600 uppercase tracking-wider hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition duration-150']) }}>
    {{ $slot }}
</button>
