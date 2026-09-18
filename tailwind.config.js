import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                serif: ['"Source Serif 4"', ...defaultTheme.fontFamily.serif],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                abyss: '#0A2622',
                pine: '#16352F',
                cypress: '#1F453D',
                moss: '#235347',
                sage: '#8EB69B',
                mint: '#DAF1DE',
                ember: '#D9A15C',
                'ember-bright': '#F0BE7D',
                'jd-bg': '#F5FAF6',
                'jd-surface-2': '#EAF6EE',
                'jd-surface-3': '#DCEFE2',
                'jd-ink-muted': '#3F6D5D',
                'jd-success': '#2F7D5C',
                'jd-warning': '#A06A17',
                'jd-danger': '#B3453C',
            },
        },
    },

    plugins: [forms],
};
