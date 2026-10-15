import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import wireuiPreset from './vendor/wireui/wireui/tailwind.config.js';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [wireuiPreset],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/wireui/wireui/src/**/*.php',
        './vendor/wireui/wireui/ts/**/*.ts',
        './vendor/wireui/wireui/src/WireUi/**/*.php',
        './vendor/wireui/wireui/src/Components/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#4461F2',
                    600: '#3651e0',
                    700: '#2d43c6',
                    800: '#2637a3',
                    900: '#1e2a7a',
                },
            },
        },
    },

    plugins: [forms],
};
