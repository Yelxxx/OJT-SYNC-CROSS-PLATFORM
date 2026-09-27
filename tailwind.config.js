import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#EAF6EC',
                    100: '#D2ECDA',
                    200: '#A9DBB8',
                    300: '#79C38F',
                    400: '#4FAE67',
                    500: '#33954B',
                    600: '#2E7D32',
                    700: '#256A29',
                    800: '#1E5722',
                    900: '#163A1D',
                    950: '#0C2710',
                },
                gold: {
                    50: '#FBF8EF',
                    100: '#F6EED6',
                    200: '#EBDDA8',
                    300: '#DFC878',
                    400: '#D4AF37',
                    500: '#C39B25',
                    600: '#A67F1D',
                    700: '#85611B',
                    800: '#6E4F1C',
                    900: '#5E421C',
                    950: '#36230F',
                },
                cream: {
                    50: '#FCFBF7',
                    100: '#F7F4ED',
                    200: '#EFE9DD',
                    300: '#E3DAC6',
                    400: '#D1C4A6',
                    500: '#BEAC85',
                    600: '#A89066',
                    700: '#8B7754',
                    800: '#726146',
                    900: '#5E503B',
                    950: '#332A1F',
                },
            },
        },
    },

    plugins: [forms],
};
