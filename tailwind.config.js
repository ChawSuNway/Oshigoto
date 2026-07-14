import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Roboto', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                // Material Design elevation levels.
                'md-1': '0 1px 3px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.24)',
                'md-2': '0 3px 6px rgba(0,0,0,.16), 0 3px 6px rgba(0,0,0,.23)',
                'md-4': '0 10px 20px rgba(0,0,0,.19), 0 6px 6px rgba(0,0,0,.23)',
                'md-8': '0 14px 28px rgba(0,0,0,.25), 0 10px 10px rgba(0,0,0,.22)',
            },
        },
    },

    plugins: [forms],
};
