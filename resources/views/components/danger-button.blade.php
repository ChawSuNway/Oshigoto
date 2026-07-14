<button {{ $attributes->merge(['type' => 'submit', 'class' => 'md-ripple inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-red-700 hover:shadow-md-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 transition duration-150']) }}>
    {{ $slot }}
</button>
