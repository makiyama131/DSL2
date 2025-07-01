import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // 見込み客 (水色)
        'bg-blue-100',
        'dark:bg-blue-900',
        'text-blue-800',
        'dark:text-blue-200',
        // 名前記録済み (オレンジ色)
        'bg-orange-100',
        'dark:bg-orange-900',
        'text-orange-800',
        'dark:text-orange-200',
        // 多分いける (黄色)
        'bg-yellow-100',
        'dark:bg-yellow-900',
        'text-yellow-800',
        'dark:text-yellow-200',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
