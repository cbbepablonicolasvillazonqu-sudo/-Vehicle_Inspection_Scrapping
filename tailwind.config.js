import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Componentes Livewire y enums (generan clases de color por estado).
        './app/Livewire/**/*.php',
        './app/Enums/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter Variable', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                // Sombra suave y elevada para barras flotantes (nav inferior).
                'elevada': '0 -1px 3px 0 rgb(0 0 0 / 0.06), 0 -1px 2px -1px rgb(0 0 0 / 0.04)',
            },
        },
    },

    plugins: [forms],
};
