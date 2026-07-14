@props(['disabled' => false])

@php
    // Auto-highlight this field with a red border when it has a validation error.
    $field = $attributes->get('name');
    $hasError = $field && collect($errors->keys())
        ->contains(fn ($k) => $k === $field || \Illuminate\Support\Str::startsWith($k, $field . '.'));
    // Material outlined text field: 1px border that turns primary (indigo) on focus.
    $stateClasses = $hasError
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-400 focus:border-indigo-600 focus:ring-indigo-600';
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => $stateClasses . ' rounded shadow-none transition']) }}>
