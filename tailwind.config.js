/** @type {import('tailwindcss').Config} */
/** Consumer `brand` / `accent` shades: keep in sync with `amazepays-mobile/src/designTokens.ts`. */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
        './resources/js/**/*.ts',
        './resources/js/**/*.jsx',
        './resources/js/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
            },
            colors: {
                brand: {
                    50: '#eef1f8',
                    100: '#d4dae9',
                    500: '#2d4490',
                    600: '#1b2b5e',
                    700: '#152248',
                    800: '#101a36',
                    950: '#080d1c',
                },
                accent: {
                    50: '#fff5eb',
                    100: '#fee4cc',
                    500: '#f5811f',
                    600: '#d96c10',
                    700: '#a4520b',
                    950: '#1d0f02',
                },
                product: {
                    canvas: '#f9fafb',
                    navy: '#0d1117',
                    primary: '#0B0B8F',
                    accent: '#FF6A00',
                },
            },
            keyframes: {
                'slide-in-right': {
                    '0%': { transform: 'translateX(100%)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
            },
            animation: {
                'slide-in-right': 'slide-in-right 0.3s ease-out',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
