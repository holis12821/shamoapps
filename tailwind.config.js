import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',          // add this
        './resources/js/**/*.vue',         // if using Vue
        './resources/**/*.html',
    ],

    safelist: [
        'bg-blue-700',
        'border-blue-700',
        'border-gray-700',
        'bg-gray-700',
        'bg-gray-800',
        'bg-red-500',
        'text-white',
        'hover:bg-red-600',
        'hover:bg-gray-800',
        'hover:bg-gray-900',
        'hover:bg-blue-800',
        'rounded-md',
        'px-2',
        'py-1',
        'm-1',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
